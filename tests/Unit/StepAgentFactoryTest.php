<?php

namespace Tests\Unit;

use App\Ai\StepAgentFactory;
use App\Enums\ModelTier;
use App\Models\ModelProfile;
use App\Models\RuleStep;
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
}
