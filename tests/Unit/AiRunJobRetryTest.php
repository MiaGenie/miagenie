<?php

namespace Tests\Unit;

use App\Enums\Modality;
use App\Enums\RuleType;
use App\Enums\RunResponseError;
use App\Enums\RunStatus;
use App\Genie\Strategy\StrategyRunner;
use App\Jobs\AiRunJob;
use App\Models\AiRun;
use App\Models\AiRunStep;
use App\Models\Rule;
use App\Models\Workspace;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Queue\Jobs\FakeJob;
use Illuminate\Support\Facades\Schema;
use Inovector\Mixpost\Facades\WorkspaceManager;
use Tests\TestCase;

/**
 * A failed run is released for another attempt unless what stopped it cannot clear in the
 * backoff. The suite has no migrated database, so the run tables are built in memory.
 */
class AiRunJobRetryTest extends TestCase
{
    protected AiRun $run;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.ai_run_job_retry', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        config()->set('database.default', 'ai_run_job_retry');

        Schema::create('genie_ai_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('rule_id')->nullable();
            $table->char('conversation_id', 36)->nullable();
            $table->json('parameters')->nullable();
            $table->tinyInteger('status');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('genie_ai_run_steps', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('run_id');
            $table->unsignedBigInteger('step_id')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->tinyInteger('modality')->nullable();
            $table->tinyInteger('status');
            $table->tinyInteger('error')->nullable();
            $table->string('error_details')->nullable();
            $table->timestamps();
        });

        WorkspaceManager::setCurrent((new Workspace)->forceFill(['id' => 7]));

        $this->run = AiRun::create(['workspace_id' => 7, 'status' => RunStatus::ERROR]);
    }

    protected function tearDown(): void
    {
        WorkspaceManager::forgetCurrent();

        parent::tearDown();
    }

    public function test_a_run_that_failed_on_a_server_error_is_released_with_the_first_backoff(): void
    {
        $this->failedStep(RunResponseError::SERVER_ERROR);

        $job = $this->job();
        $job->releaseIfRetryable();

        $job->assertReleased(delay: 15);
    }

    public function test_a_run_that_failed_on_a_rate_limit_is_released(): void
    {
        $this->failedStep(RunResponseError::RATE_LIMIT_EXCEEDED);

        $job = $this->job(attempts: 2);
        $job->releaseIfRetryable();

        $job->assertReleased(delay: 60);
    }

    public function test_a_run_that_ran_out_of_credits_is_not_released(): void
    {
        $this->failedStep(RunResponseError::SERVER_ERROR, position: 1, status: RunStatus::COMPLETE);
        $this->failedStep(RunResponseError::INSUFFICIENT_CREDITS, position: 2);

        $job = $this->job();
        $job->releaseIfRetryable();

        $job->assertNotReleased();
    }

    public function test_a_credits_error_left_on_a_step_that_is_no_longer_failed_does_not_block_a_retry(): void
    {
        $this->failedStep(RunResponseError::INSUFFICIENT_CREDITS, position: 1, status: RunStatus::COMPLETE);
        $this->failedStep(RunResponseError::SERVER_ERROR, position: 2);

        $job = $this->job();
        $job->releaseIfRetryable();

        $job->assertReleased(delay: 15);
    }

    public function test_a_run_is_not_released_once_its_attempts_are_used_up(): void
    {
        $this->failedStep(RunResponseError::SERVER_ERROR);

        $job = $this->job(attempts: 3);
        $job->releaseIfRetryable();

        $job->assertNotReleased();
    }

    public function test_a_run_that_did_not_fail_is_not_released(): void
    {
        $this->run->update(['status' => RunStatus::RUNNING]);

        $job = $this->job();
        $job->releaseIfRetryable();

        $job->assertNotReleased();
    }

    /**
     * A failed strategy run can be replaced by an admin's regeneration before its retry comes
     * back; the retry then leaves it as it stopped rather than finishing a strategy nobody reads.
     */
    public function test_a_superseded_run_is_neither_retried_nor_carried_on(): void
    {
        $this->failedStep(RunResponseError::SERVER_ERROR);

        $runner = $this->createMock(StrategyRunner::class);
        $runner->method('isSuperseded')->willReturn(true);
        $runner->expects($this->never())->method('retryFailed');
        $runner->expects($this->never())->method('advance');
        $this->app->instance(StrategyRunner::class, $runner);

        $job = $this->job(attempts: 2);
        $job->handle();

        $job->assertNotReleased();
        $this->assertSame(RunStatus::ERROR, $this->run->fresh()->status);
    }

    /**
     * The job's uuid survives its own retries, which is what lets a job that threw after claiming
     * a step take that step back instead of being refused until the claim expires.
     */
    public function test_the_job_claims_its_steps_with_its_queue_uuid(): void
    {
        $this->run->update(['status' => RunStatus::RUNNING]);

        $runner = $this->createMock(StrategyRunner::class);
        $runner->method('isSuperseded')->willReturn(false);
        $runner->expects($this->once())
            ->method('advance')
            ->with($this->isInstanceOf(AiRun::class), 'the-job-uuid')
            ->willReturn(false);
        $this->app->instance(StrategyRunner::class, $runner);

        $this->job(attempts: 2, uuid: 'the-job-uuid')->handle();
    }

    protected function job(int $attempts = 1, ?string $uuid = null): AiRunJob
    {
        $job = new class($this->run) extends AiRunJob
        {
            public function releaseIfRetryable(): void
            {
                parent::releaseIfRetryable();
            }
        };

        $job->run
            ->setRelation('workspace', (new Workspace)->forceFill(['id' => 7]))
            ->setRelation('rule', (new Rule)->forceFill(['rule_type' => RuleType::STRATEGY]));

        $fake = new class($uuid) extends FakeJob
        {
            public function __construct(protected ?string $uuid) {}

            public function getRawBody()
            {
                return (string) json_encode(['uuid' => $this->uuid]);
            }
        };
        $fake->attempts = $attempts;

        return $job->setJob($fake);
    }

    protected function failedStep(RunResponseError $error, int $position = 1, RunStatus $status = RunStatus::ERROR): AiRunStep
    {
        return AiRunStep::create([
            'run_id' => $this->run->id,
            'position' => $position,
            'modality' => Modality::TEXT,
            'status' => $status,
            'error' => $error,
        ]);
    }
}
