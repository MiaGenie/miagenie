<?php

namespace Tests\Unit;

use App\Ai\Agents\TranslationAgent;
use App\Enums\Modality;
use App\Models\TokenUsage;
use App\Models\Workspace;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Schema;
use Inovector\Mixpost\Facades\WorkspaceManager;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Image;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\ImageUsage;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\ImageResponse;
use Tests\TestCase;

/**
 * Every SDK call made while a workspace is current leaves one usage row for it. The suite has no
 * migrated database, so the table is built in memory.
 */
class RecordTokenUsageTest extends TestCase
{
    protected Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.record_token_usage', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        config()->set('database.default', 'record_token_usage');

        Schema::create('genie_token_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id');
            $table->tinyInteger('modality')->default(Modality::TEXT->value);
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->timestamps();
        });

        $this->workspace = (new Workspace)->forceFill(['id' => 7]);

        WorkspaceManager::setCurrent($this->workspace);
    }

    protected function tearDown(): void
    {
        WorkspaceManager::forgetCurrent();

        parent::tearDown();
    }

    public function test_an_image_call_is_recorded_for_the_current_workspace(): void
    {
        Image::fake([
            new ImageResponse(new Collection, new ImageUsage(inputTokens: 120, outputTokens: 4000), new Meta('openai', 'gpt-image-2')),
        ]);

        Image::of('A red chair')->generate(Lab::OpenAI, 'gpt-image-2');

        $usage = TokenUsage::byWorkspace($this->workspace)->sole();

        $this->assertSame(Modality::IMAGE, $usage->modality);
        $this->assertSame('openai', $usage->provider);
        $this->assertSame('gpt-image-2', $usage->model);
        $this->assertSame(120, $usage->input_tokens);
        $this->assertSame(4000, $usage->output_tokens);
    }

    public function test_a_text_call_is_recorded_with_the_provider_and_model_of_the_answer(): void
    {
        event($this->agentPrompted(
            new TextUsage(inputTokens: 365, outputTokens: 65, cacheReadInputTokens: 60, cacheWriteInputTokens: 5, reasoningTokens: 25),
            new Meta('gemini', 'gemini-3-pro'),
        ));

        $usage = TokenUsage::byWorkspace($this->workspace)->sole();

        $this->assertSame(Modality::TEXT, $usage->modality);
        $this->assertSame('gemini', $usage->provider);
        $this->assertSame('gemini-3-pro', $usage->model);
        $this->assertSame(365, $usage->input_tokens);
        $this->assertSame(65, $usage->output_tokens);
    }

    public function test_prompting_an_agent_reaches_the_listener(): void
    {
        TranslationAgent::fake([['translations' => []]]);

        (new TranslationAgent)->prompt('Hello');

        $this->assertSame(Modality::TEXT, TokenUsage::byWorkspace($this->workspace)->sole()->modality);
    }

    public function test_nothing_is_recorded_without_a_current_workspace(): void
    {
        WorkspaceManager::forgetCurrent();

        Image::fake();
        Image::of('A red chair')->generate(Lab::OpenAI, 'gpt-image-2');

        event($this->agentPrompted(new TextUsage(inputTokens: 10, outputTokens: 10), new Meta('openai', 'gpt-5')));

        $this->assertSame(0, TokenUsage::withoutWorkspace()->count());
    }

    public function test_a_failed_write_is_reported_instead_of_failing_the_call(): void
    {
        Exceptions::fake();
        Schema::drop('genie_token_usages');

        event($this->agentPrompted(new TextUsage(inputTokens: 10, outputTokens: 10), new Meta('openai', 'gpt-5')));

        Exceptions::assertReported(QueryException::class);
    }

    protected function agentPrompted(TextUsage $usage, Meta $meta): AgentPrompted
    {
        return new AgentPrompted(
            'invocation',
            $this->createStub(AgentPrompt::class),
            new AgentResponse('invocation', 'Answer', $usage, $meta),
        );
    }
}
