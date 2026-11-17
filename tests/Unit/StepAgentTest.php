<?php

namespace Tests\Unit;

use App\Ai\Agents\StepAgent;
use App\Enums\ModelTier;
use App\Enums\RuleSubType;
use App\Models\ModelProfile;
use App\Models\RuleStep;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Providers\Tools\WebSearch;
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

    public function test_a_step_that_does_not_search_has_no_tools(): void
    {
        $agent = $this->agent();

        $this->assertInstanceOf(HasTools::class, $agent);
        $this->assertSame([], $agent->tools());
    }

    public function test_a_searching_step_carries_a_web_search_with_its_cap_and_domains(): void
    {
        $tools = $this->agent([
            'rule_sub_type' => RuleSubType::BRIEFINGS,
            'web_search' => true,
            'web_search_max' => 4,
            'web_search_domains' => ['statista.com', 'example.org'],
        ])->tools();

        $this->assertCount(1, $tools);
        $this->assertInstanceOf(WebSearch::class, $tools[0]);
        $this->assertSame(4, $tools[0]->maxSearches);
        $this->assertSame(['statista.com', 'example.org'], $tools[0]->allowedDomains);
    }

    public function test_a_search_without_a_cap_or_domains_is_left_open(): void
    {
        $tools = $this->agent(['rule_sub_type' => RuleSubType::CHANNELS, 'web_search' => true])->tools();

        $this->assertNull($tools[0]->maxSearches);
        $this->assertSame([], $tools[0]->allowedDomains);
    }

    /**
     * The step form refuses the switch outside the strategy sub types, but an imported version
     * copies the column as exported; the agent must not search for such a step all the same.
     */
    public function test_a_switch_left_on_for_a_sub_type_that_may_not_search_carries_no_tools(): void
    {
        foreach ([RuleSubType::IDEAS_FORMAT, RuleSubType::POSTS_CONTENT, null] as $subType) {
            $agent = $this->agent(['rule_sub_type' => $subType, 'web_search' => true, 'web_search_max' => 3]);

            $this->assertSame([], $agent->tools(), $subType?->name ?? 'no sub type');
        }
    }
}
