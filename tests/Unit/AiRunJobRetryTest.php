<?php

namespace Tests\Unit;

use App\Enums\Modality;
use App\Enums\RunResponseError;
use App\Enums\RunStatus;
use App\Jobs\AiRunJob;
use App\Models\AiRun;
use App\Models\AiRunStep;
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

    protected function job(int $attempts = 1): AiRunJob
    {
        $job = new class($this->run) extends AiRunJob
        {
            public function releaseIfRetryable(): void
            {
                parent::releaseIfRetryable();
            }
        };

        $fake = new FakeJob;
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
