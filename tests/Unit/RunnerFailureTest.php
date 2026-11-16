<?php

namespace Tests\Unit;

use App\Ai\StepAgentFactory;
use App\Enums\Modality;
use App\Enums\RunResponseError;
use App\Enums\RunStatus;
use App\Genie\Posts\PostsRunner;
use App\Genie\Runs\RunPrompt;
use App\Genie\Runs\RunState;
use App\Models\AiRun;
use App\Models\AiRunStep;
use App\Models\Workspace;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Inovector\Mixpost\Facades\WorkspaceManager;
use Laravel\Ai\Exceptions\InsufficientCreditsException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use RuntimeException;
use Tests\TestCase;
use Throwable;

/**
 * Runner::fail() records why a step failed, which is what AiRunJob reads to decide whether the
 * run is worth another attempt. The suite has no migrated database, so the run tables are built
 * in memory.
 */
class RunnerFailureTest extends TestCase
{
    protected AiRun $run;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.runner_failure', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        config()->set('database.default', 'runner_failure');

        Schema::create('genie_ai_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('workspace_id');
            $table->tinyInteger('status');
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

        Log::spy();

        WorkspaceManager::setCurrent((new Workspace)->forceFill(['id' => 7]));

        $this->run = AiRun::create(['workspace_id' => 7, 'status' => RunStatus::RUNNING]);
    }

    protected function tearDown(): void
    {
        WorkspaceManager::forgetCurrent();

        parent::tearDown();
    }

    public function test_a_step_that_ran_out_of_credits_records_the_credits_error(): void
    {
        $step = $this->failWith(InsufficientCreditsException::forProvider('openai', 429));

        $this->assertSame(RunResponseError::INSUFFICIENT_CREDITS, $step->error);
        $this->assertSame(RunStatus::ERROR, $step->status);
        $this->assertSame(RunStatus::ERROR, $this->run->fresh()->status);
    }

    public function test_a_rate_limited_step_records_the_rate_limit(): void
    {
        $step = $this->failWith(RateLimitedException::forProvider('anthropic', 429));

        $this->assertSame(RunResponseError::RATE_LIMIT_EXCEEDED, $step->error);
    }

    public function test_an_overloaded_provider_records_a_server_error(): void
    {
        $step = $this->failWith(ProviderOverloadedException::forProvider('anthropic', 529));

        $this->assertSame(RunResponseError::SERVER_ERROR, $step->error);
    }

    public function test_any_other_failure_records_a_server_error_and_keeps_its_message(): void
    {
        $step = $this->failWith(new RuntimeException('Step [4] returned no usable output.'));

        $this->assertSame(RunResponseError::SERVER_ERROR, $step->error);
        $this->assertSame('Step [4] returned no usable output.', $step->error_details);
    }

    protected function failWith(Throwable $exception): AiRunStep
    {
        $runner = new class(new StepAgentFactory, new RunState) extends PostsRunner
        {
            public function failFor(AiRunStep $runStep, AiRun $run, Throwable $exception): bool
            {
                return $this->fail($runStep, $run, null, $exception);
            }

            protected function log(?RunPrompt $prompt, AiRunStep $runStep, array $response, ?array $request = null): void {}
        };

        $runStep = AiRunStep::create([
            'run_id' => $this->run->id,
            'position' => 1,
            'modality' => Modality::TEXT,
            'status' => RunStatus::RUNNING,
        ]);

        $this->assertFalse($runner->failFor($runStep, $this->run, $exception));

        return $runStep->fresh();
    }
}
