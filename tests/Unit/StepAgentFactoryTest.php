<?php

namespace Tests\Unit;

use App\Ai\StepAgentFactory;
use App\Enums\ModelTier;
use App\Models\ModelProfile;
use App\Models\RuleStep;
use Laravel\Ai\Contracts\Providers\SupportsWebSearch;
use Laravel\Ai\Providers\AnthropicProvider;
use Laravel\Ai\Providers\AzureOpenAiProvider;
use Laravel\Ai\Providers\DeepSeekProvider;
use Laravel\Ai\Providers\GeminiProvider;
use Laravel\Ai\Providers\OpenAiProvider;
use Laravel\Ai\Providers\OpenRouterProvider;
use Laravel\Ai\Providers\XaiProvider;
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
