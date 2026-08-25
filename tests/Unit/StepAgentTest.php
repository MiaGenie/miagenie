<?php

namespace Tests\Unit;

use App\Ai\Agents\StepAgent;
use App\Enums\ModelTier;
use App\Models\ModelProfile;
use App\Models\RuleStep;
use Laravel\Ai\Contracts\Providers\TextProvider;
use ReflectionMethod;
use Tests\TestCase;

class StepAgentTest extends TestCase
{
    /**
     * Build an unsaved step with its profile attached, so generation options can be
     * exercised without touching the database.
     *
     * @param  array<string, mixed>  $stepAttributes
     * @param  array<string, mixed>  $profileAttributes
     */
    protected function agent(array $stepAttributes = [], array $profileAttributes = []): StepAgent
    {
        $profile = new ModelProfile(array_merge([
            'name' => 'Test',
            'provider' => 'openai',
            'model_tier' => ModelTier::DEFAULT,
        ], $profileAttributes));

        $step = new RuleStep(array_merge([
            'name' => 'Step',
            'instructions' => ['en-GB' => 'Do the thing.'],
        ], $stepAttributes));

        $step->setRelation('modelProfile', $profile);

        return new StepAgent($step, 'en-GB');
    }

    /**
     * A provider whose three tiers are distinguishable, so the mapping can be asserted.
     */
    protected function provider(): TextProvider
    {
        $provider = $this->createMock(TextProvider::class);

        $provider->method('defaultTextModel')->willReturn('the-default');
        $provider->method('cheapestTextModel')->willReturn('the-cheapest');
        $provider->method('smartestTextModel')->willReturn('the-smartest');

        return $provider;
    }

    /**
     * getDefaultModelFor() is the SDK's own hook, and protected, so it is called reflectively
     * rather than through a full prompt.
     */
    protected function resolveModel(StepAgent $agent): string
    {
        $method = new ReflectionMethod($agent, 'getDefaultModelFor');

        return $method->invoke($agent, $this->provider());
    }

    public function test_instructions_come_from_the_step_translation(): void
    {
        $this->assertSame('Do the thing.', (string) $this->agent()->instructions());
    }

    public function test_a_tiered_profile_names_no_model_and_resolves_through_the_provider(): void
    {
        $agent = $this->agent();

        $this->assertNull($agent->model());
        $this->assertSame('the-default', $this->resolveModel($agent));
    }

    public function test_the_cheapest_and_smartest_tiers_map_to_the_provider_accessors(): void
    {
        $this->assertSame(
            'the-cheapest',
            $this->resolveModel($this->agent([], ['model_tier' => ModelTier::CHEAPEST]))
        );

        $this->assertSame(
            'the-smartest',
            $this->resolveModel($this->agent([], ['model_tier' => ModelTier::SMARTEST]))
        );
    }

    public function test_the_other_tier_names_the_model_itself(): void
    {
        $agent = $this->agent([], [
            'model_tier' => ModelTier::OTHER,
            'model' => 'gpt-4o-mini',
        ]);

        $this->assertSame('gpt-4o-mini', $agent->model());

        // Promptable never asks for a default once the agent named a model, but a profile that
        // lost its model name must still resolve to something promptable.
        $this->assertSame('the-default', $this->resolveModel($agent));
    }
}
