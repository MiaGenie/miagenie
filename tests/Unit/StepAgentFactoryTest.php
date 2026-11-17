<?php

namespace Tests\Unit;

use App\Ai\StepAgentFactory;
use App\Enums\ModelTier;
use App\Enums\RuleSubType;
use App\Models\ModelProfile;
use App\Models\RuleStep;
use Laravel\Ai\Contracts\Providers\SupportsWebSearch;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Providers\AnthropicProvider;
use Laravel\Ai\Providers\AzureOpenAiProvider;
use Laravel\Ai\Providers\DeepSeekProvider;
use Laravel\Ai\Providers\GeminiProvider;
use Laravel\Ai\Providers\OpenAiProvider;
use Laravel\Ai\Providers\OpenRouterProvider;
use Laravel\Ai\Providers\XaiProvider;
use RuntimeException;
use Tests\TestCase;

class StepAgentFactoryTest extends TestCase
{
    protected function step(array $profile): RuleStep
    {
        $step = new RuleStep(['name' => 'Step']);
        $step->setRelation('modelProfile', new ModelProfile($profile));

        return $step;
    }

    public function test_a_tiered_profile_leaves_the_image_model_to_the_provider(): void
    {
        $factory = new StepAgentFactory;

        foreach ([ModelTier::DEFAULT, ModelTier::CHEAPEST, ModelTier::SMARTEST] as $tier) {
            $this->assertNull($factory->imageModel($this->step(['provider' => 'openai', 'model_tier' => $tier, 'model' => 'ignored'])));
        }
    }

    public function test_an_explicit_profile_names_the_image_model(): void
    {
        $step = $this->step(['provider' => 'openai', 'model_tier' => ModelTier::OTHER, 'model' => 'gpt-image-1']);

        $this->assertSame('gpt-image-1', (new StepAgentFactory)->imageModel($step));
    }

    public function test_a_step_without_a_profile_has_no_image_model(): void
    {
        $this->assertNull((new StepAgentFactory)->imageModel(new RuleStep(['name' => 'Bare'])));
    }

    public function test_only_image_capable_providers_support_images(): void
    {
        $this->assertTrue(StepAgentFactory::supportsImages(new ModelProfile(['provider' => 'openai'])));
        $this->assertTrue(StepAgentFactory::supportsImages($this->step(['provider' => 'gemini'])));
        $this->assertFalse(StepAgentFactory::supportsImages(new ModelProfile(['provider' => 'anthropic'])));
        $this->assertFalse(StepAgentFactory::supportsImages(new RuleStep(['name' => 'Bare'])));
    }

    public function test_only_web_search_capable_providers_support_web_search(): void
    {
        $this->assertTrue(StepAgentFactory::supportsWebSearch(new ModelProfile(['provider' => 'openai'])));
        $this->assertTrue(StepAgentFactory::supportsWebSearch($this->step(['provider' => 'anthropic'])));
        $this->assertFalse(StepAgentFactory::supportsWebSearch(new ModelProfile(['provider' => 'deepseek'])));
        $this->assertFalse(StepAgentFactory::supportsWebSearch(new RuleStep(['name' => 'Bare'])));
    }

    /**
     * A profile's provider is a config/ai.php key, and a key may run on another driver: what the
     * provider can do is the driver's.
     */
    public function test_capabilities_follow_the_driver_behind_the_provider_key(): void
    {
        config()->set('ai.providers.openai-eu', ['driver' => 'openai', 'key' => 'test']);
        config()->set('ai.providers.local', ['driver' => 'openai-compatible', 'key' => 'test']);

        $this->assertSame('openai', (new ModelProfile(['provider' => 'openai-eu']))->driver());
        $this->assertTrue(StepAgentFactory::supportsWebSearch(new ModelProfile(['provider' => 'openai-eu'])));
        $this->assertTrue(StepAgentFactory::supportsImages(new ModelProfile(['provider' => 'openai-eu'])));

        $this->assertFalse(StepAgentFactory::supportsWebSearch(new ModelProfile(['provider' => 'local'])));
        $this->assertFalse(StepAgentFactory::supportsImages(new ModelProfile(['provider' => 'local'])));

        config()->set('ai.providers.openai.driver', 'openai-compatible');

        $this->assertFalse(StepAgentFactory::supportsWebSearch(new ModelProfile(['provider' => 'openai'])));
        $this->assertFalse(StepAgentFactory::supportsImages(new ModelProfile(['provider' => 'openai'])));
    }

    public function test_a_provider_missing_from_the_config_is_its_own_driver(): void
    {
        $this->assertSame('xai', (new ModelProfile(['provider' => 'xai']))->driver());
        $this->assertSame('unlisted', (new ModelProfile(['provider' => 'unlisted']))->driver());
        $this->assertNull((new ModelProfile)->driver());
    }

    public function test_bedrock_cannot_generate_images(): void
    {
        $this->assertNotContains('bedrock', StepAgentFactory::IMAGE_PROVIDERS);
        $this->assertFalse(StepAgentFactory::supportsImages(new ModelProfile(['provider' => 'bedrock'])));
    }

    /**
     * The SDK drops a WebSearch tool the provider cannot run instead of failing, so a step whose
     * profile was moved to such a provider after it was saved must be stopped before the call.
     */
    public function test_a_searching_step_whose_provider_cannot_search_is_refused(): void
    {
        $step = $this->step(['provider' => 'deepseek', 'model_tier' => ModelTier::DEFAULT]);
        $step->forceFill(['id' => 12, 'rule_sub_type' => RuleSubType::BRIEFINGS, 'web_search' => true]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Step [12] searches the web, but its profile\'s provider [deepseek] cannot.');

        (new StepAgentFactory)->prepare($step);
    }

    public function test_a_searching_step_on_a_provider_that_can_search_is_prepared(): void
    {
        $step = $this->step(['provider' => 'xai', 'model_tier' => ModelTier::DEFAULT]);
        $step->forceFill(['rule_sub_type' => RuleSubType::CHANNELS, 'web_search' => true]);

        $this->assertSame(Lab::xAI, (new StepAgentFactory)->prepare($step));
    }

    public function test_a_switch_left_on_for_a_sub_type_that_may_not_search_is_prepared_on_any_provider(): void
    {
        $step = $this->step(['provider' => 'mistral', 'model_tier' => ModelTier::DEFAULT]);
        $step->forceFill(['rule_sub_type' => RuleSubType::IDEAS_FORMAT, 'web_search' => true]);

        $this->assertSame(Lab::Mistral, (new StepAgentFactory)->prepare($step));
    }

    public function test_a_step_that_does_not_search_is_prepared_on_any_provider(): void
    {
        $step = $this->step(['provider' => 'mistral', 'model_tier' => ModelTier::DEFAULT]);
        $step->forceFill(['web_search' => false]);

        $this->assertSame(Lab::Mistral, (new StepAgentFactory)->prepare($step));
    }

    /**
     * The list is kept by hand, like the image one; this fails when an SDK upgrade drops web
     * search from a listed provider, or when the class a provider name maps to changes.
     */
    public function test_the_web_search_providers_are_the_sdk_providers_that_search(): void
    {
        $classes = [
            'openai' => OpenAiProvider::class,
            'azure' => AzureOpenAiProvider::class,
            'anthropic' => AnthropicProvider::class,
            'gemini' => GeminiProvider::class,
            'xai' => XaiProvider::class,
            'openrouter' => OpenRouterProvider::class,
        ];

        $this->assertSame(array_keys($classes), StepAgentFactory::WEB_SEARCH_PROVIDERS);

        foreach ($classes as $provider => $class) {
            $this->assertTrue(is_subclass_of($class, SupportsWebSearch::class), $provider);
        }

        $this->assertFalse(is_subclass_of(DeepSeekProvider::class, SupportsWebSearch::class));
    }
}
