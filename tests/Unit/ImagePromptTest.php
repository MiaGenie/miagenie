<?php

namespace Tests\Unit;

use App\Enums\ModelTier;
use App\Genie\Images\ImageContext;
use App\Genie\Images\ImagePrompt;
use App\Models\AiRun;
use App\Models\ModelProfile;
use App\Models\Rule;
use App\Models\RuleStep;
use App\Models\Strategy;
use App\Models\Version;
use App\Models\WorkspaceFile;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ImagePromptTest extends TestCase
{
    /**
     * A run with an empty version and one strategy field, so `{{{strategy.*}}}` resolves without
     * a database.
     */
    protected function aiRun(): AiRun
    {
        $version = new Version(['name' => 'Test']);
        $version->setRelation('briefings', new Collection);
        $version->setRelation('strategies', new Collection);

        $rule = new Rule;
        $rule->setRelation('version', $version);

        $run = new AiRun(['parameters' => ['post_format' => 1]]);
        $run->setRelation('rule', $rule);
        $run->setRelation('strategy', new Strategy(['content' => ['tone_of_voice' => 'Warm']]));

        return $run;
    }

    protected function step(string $message, array $profile = []): RuleStep
    {
        $step = new RuleStep([
            'name' => 'Hero image',
            'message' => ['en-GB' => $message],
            'response_format' => 'text',
        ]);

        $step->setRelation('modelProfile', new ModelProfile(array_merge([
            'provider' => 'openai',
            'model_tier' => ModelTier::DEFAULT,
        ], $profile)));

        return $step;
    }

    public function test_the_slot_placeholders_and_the_strategy_are_substituted(): void
    {
        $prompt = new ImagePrompt(
            $this->aiRun(),
            $this->step('Image {{{position}}}: {{{brief}}}. Tone: {{{strategy.tone_of_voice}}}. Notes: {{{feedback}}}.'),
            'en-GB',
            new ImageContext('A red chair', null, 2),
        );

        $this->assertSame('Image 2: A red chair. Tone: Warm. Notes: .', $prompt->text());
    }

    public function test_feedback_fills_its_placeholder_without_a_fallback_sentence(): void
    {
        $prompt = new ImagePrompt(
            $this->aiRun(),
            $this->step('{{{brief}}} — {{{feedback}}}'),
            'en-GB',
            new ImageContext('A red chair', 'Make it blue'),
        );

        $this->assertSame('A red chair — Make it blue', $prompt->text());
    }

    public function test_feedback_is_appended_when_the_template_never_mentions_it(): void
    {
        $prompt = new ImagePrompt(
            $this->aiRun(),
            $this->step('A picture of {{{brief}}}'),
            'en-GB',
            new ImageContext('a red chair', 'Make it blue'),
        );

        $text = $prompt->text();

        $this->assertStringStartsWith("A picture of a red chair\n\n", $text);
        $this->assertStringContainsString('Make it blue', $text);
        $this->assertStringContainsString('Keep everything else unchanged', $text);
    }

    public function test_nothing_is_appended_without_feedback(): void
    {
        $prompt = new ImagePrompt($this->aiRun(), $this->step('A picture of {{{brief}}}'), 'en-GB', new ImageContext('a red chair'));

        $this->assertSame('A picture of a red chair', $prompt->text());
    }

    public function test_describe_records_the_slot_and_the_reference(): void
    {
        $reference = new WorkspaceFile(['uuid' => 'file-uuid', 'path' => 'x.png', 'disk' => 'public']);

        $prompt = new ImagePrompt(
            $this->aiRun(),
            $this->step('{{{brief}}}', ['model_tier' => ModelTier::OTHER, 'model' => 'gpt-image-1']),
            'en-GB',
            new ImageContext('A red chair', 'Bluer', 2),
            $reference,
        );

        $described = $prompt->describe();

        $this->assertSame('image', $described['modality']);
        $this->assertSame(2, $described['position']);
        $this->assertSame('A red chair', $described['brief']);
        $this->assertSame('Bluer', $described['feedback']);
        $this->assertSame('file-uuid', $described['reference']);
        $this->assertSame('openai', $described['provider']);
        $this->assertSame('gpt-image-1', $described['model']);
        $this->assertSame(['post_format' => 1], $described['parameters']);
        $this->assertSame($reference, $prompt->reference());
        $this->assertSame('A red chair', $prompt->context()->brief);
    }
}
