<?php

namespace Tests\Unit;

use App\Ai\Agents\StepAgent;
use App\Ai\StepAgentFactory;
use App\Enums\Modality;
use App\Enums\RunStatus;
use App\Genie\Runs\Runner;
use App\Genie\Runs\RunPrompt;
use App\Genie\Runs\RunState;
use App\Genie\Runs\StepGate;
use App\Genie\Runs\StepOutput;
use App\Models\AiRun;
use App\Models\AiRunStep;
use App\Models\Log as RunLog;
use App\Models\RuleStep;
use App\Models\Workspace;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Inovector\Mixpost\Facades\WorkspaceManager;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use RuntimeException;
use Tests\TestCase;

/**
 * A text step's log entry carries the tokens its call consumed, and a call that answered is
 * counted on exactly one entry however the step ends. The suite has no migrated database, so the
 * run tables are built in memory.
 */
class RunnerTokenCountsTest extends TestCase
{
    protected AiRun $run;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.runner_token_counts', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        config()->set('database.default', 'runner_token_counts');

        Schema::create('genie_ai_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('workspace_id');
            $table->string('conversation_id')->nullable();
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
            $table->string('invocation_id')->nullable();
            $table->string('message_id')->nullable();
            $table->json('output')->nullable();
            $table->tinyInteger('error')->nullable();
            $table->string('error_details')->nullable();
            $table->unsignedInteger('duration')->nullable();
            $table->timestamps();
        });

        Schema::create('genie_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type');
            $table->string('action');
            $table->unsignedBigInteger('run_step_id')->nullable();
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->smallInteger('duration')->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->timestamps();
        });

        Log::spy();

        $workspace = (new Workspace)->forceFill(['id' => 7, 'locale' => 'en-GB']);

        WorkspaceManager::setCurrent($workspace);

        $this->run = AiRun::create(['workspace_id' => 7, 'status' => RunStatus::RUNNING]);
        $this->run->setRelation('workspace', $workspace);
    }

    protected function tearDown(): void
    {
        WorkspaceManager::forgetCurrent();

        parent::tearDown();
    }

    public function test_an_answered_step_logs_the_tokens_of_its_call(): void
    {
        $runStep = $this->runStep();

        $this->assertTrue($this->runner($this->answering('{"summary": "ok"}'))->runFor($runStep));

        $entry = RunLog::sole();

        $this->assertSame($runStep->id, $entry->run_step_id);
        $this->assertSame(2280, $entry->input_tokens);
        $this->assertSame(829, $entry->output_tokens);
        $this->assertSame(RunStatus::COMPLETE, $runStep->fresh()->status);
    }

    public function test_an_answer_without_usable_output_logs_its_tokens_on_the_error_entry(): void
    {
        $runStep = $this->runStep();

        $this->assertFalse($this->runner($this->answering('not json'))->runFor($runStep));

        $entry = RunLog::sole();

        $this->assertStringContainsString('returned no usable output', $entry->response['error']);
        $this->assertSame(2280, $entry->input_tokens);
        $this->assertSame(829, $entry->output_tokens);
        $this->assertSame(RunStatus::ERROR, $runStep->fresh()->status);
    }

    public function test_a_step_that_fails_after_its_call_was_logged_does_not_count_the_call_twice(): void
    {
        $state = new class extends RunState
        {
            public function completed(AiRunStep $runStep): void
            {
                throw new RuntimeException('Review gate unavailable.');
            }
        };

        $this->assertFalse($this->runner($this->answering('{"summary": "ok"}'), $state)->runFor($this->runStep()));

        $entries = RunLog::oldest('id')->get();

        $this->assertCount(2, $entries);
        $this->assertSame([2280, null], $entries->pluck('input_tokens')->all());
        $this->assertSame([829, null], $entries->pluck('output_tokens')->all());
        $this->assertSame('Review gate unavailable.', $entries[1]->response['error']);
    }

    public function test_a_call_that_never_answered_logs_no_tokens(): void
    {
        $agent = $this->createMock(StepAgent::class);
        $agent->method('prompt')->willThrowException(new RuntimeException('Provider down.'));

        $this->assertFalse($this->runner($agent)->runFor($this->runStep()));

        $entry = RunLog::sole();

        $this->assertSame('Provider down.', $entry->response['error']);
        $this->assertNull($entry->input_tokens);
        $this->assertNull($entry->output_tokens);
    }

    protected function answering(string $text): StepAgent
    {
        $agent = $this->createMock(StepAgent::class);
        $agent->method('prompt')->willReturn(new AgentResponse(
            'invocation-1',
            $text,
            new TextUsage(inputTokens: 2280, outputTokens: 829, cacheWriteInputTokens: 2277, reasoningTokens: 410),
            new Meta('openai', 'gpt-6.1-sol'),
        ));

        return $agent;
    }

    protected function runner(StepAgent $agent, RunState $state = new RunState): Runner
    {
        $agents = $this->createMock(StepAgentFactory::class);
        $agents->method('provider')->willReturn(Lab::OpenAI);
        $agents->method('timeout')->willReturn(60);

        $prompt = $this->createMock(RunPrompt::class);
        $prompt->method('text')->willReturn('Diagnose the brand.');
        $prompt->method('describe')->willReturn(['provider' => 'openai', 'model' => 'gpt-6.1-sol']);

        $gate = $this->createMock(StepGate::class);

        $runner = new class($agents, $state, $this->createMock(StepOutput::class), $gate) extends Runner
        {
            public StepAgent $stepAgent;

            public RunPrompt $runPrompt;

            public function runFor(AiRunStep $runStep): bool
            {
                return $this->run($runStep);
            }

            protected function prompt(AiRunStep $runStep, string $locale): RunPrompt
            {
                return $this->runPrompt;
            }

            protected function agent(AiRunStep $runStep, string $locale): StepAgent
            {
                return $this->stepAgent;
            }

            protected function label(): string
            {
                return 'test';
            }
        };

        $runner->stepAgent = $agent;
        $runner->runPrompt = $prompt;

        return $runner;
    }

    protected function runStep(): AiRunStep
    {
        $runStep = AiRunStep::create([
            'run_id' => $this->run->id,
            'position' => 1,
            'modality' => Modality::TEXT,
            'status' => RunStatus::RUNNING,
        ]);

        return $runStep
            ->setRelation('run', $this->run)
            ->setRelation('step', new RuleStep(['name' => 'Initial Diagnosis']));
    }
}
