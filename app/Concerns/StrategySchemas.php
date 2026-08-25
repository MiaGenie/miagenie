<?php

namespace App\Concerns;

use App\Genie\Schema\StepSchemaBuilder;
use App\Genie\Schema\SubFieldMetaBuilder;
use App\Models\Rule;
use App\Models\RuleStep;
use App\Models\Strategy;
use Throwable;

trait StrategySchemas
{
    /**
     * Every strategy field's schema, keyed by field code_name.
     *
     * Compiled from the sub-field trees, the same way the schema sent to the provider is built,
     * so what the page renders and what the model was asked for cannot drift. The step's stored
     * `json_schema` is legacy and deliberately not consulted.
     *
     * The rule is reached through whichever run produced the strategy: the new pipeline owns the
     * link from `genie_ai_runs.strategy_id`, while a strategy from the old one carries `run_id`.
     *
     * @return array<string, mixed>
     */
    protected function getStrategySchemas(Strategy $strategy): array
    {
        $rule = $this->ruleFor($strategy);

        if (! $rule) {
            return [];
        }

        $locale = $strategy->workspace->locale ?? app()->getLocale();
        $builder = new StepSchemaBuilder;

        return $rule->steps->reduce(
            fn (array $schemas, RuleStep $step): array => array_merge(
                $schemas,
                $this->propertiesFor($builder, $step, $locale),
            ),
            [],
        );
    }

    /**
     * What the pages need to know about each field's sub-fields beyond its schema — today, whether
     * a node may be edited.
     *
     * Keyed by field `code_name` and shaped like the compiled schema, so a page can walk a value,
     * its schema and its meta together.
     *
     * @return array<string, mixed>
     */
    protected function getStrategyMeta(Strategy $strategy): array
    {
        $rule = $this->ruleFor($strategy);

        if (! $rule) {
            return [];
        }

        $schema = new StepSchemaBuilder;
        $meta = new SubFieldMetaBuilder;

        return $rule->steps->reduce(
            fn (array $carry, RuleStep $step): array => array_merge(
                $carry,
                $meta->forFields($schema->resolveFields($step) ?? []),
            ),
            [],
        );
    }

    /**
     * A step that cannot compile is skipped rather than fatal.
     *
     * Generation refuses outright — a run must not write to keys nobody defined — but a page
     * showing an older strategy should still render the fields it can.
     *
     * @return array<string, mixed>
     */
    protected function propertiesFor(StepSchemaBuilder $builder, RuleStep $step, string $locale): array
    {
        try {
            return $builder->forStep($step, $locale)['properties'] ?? [];
        } catch (Throwable) {
            return [];
        }
    }

    protected function ruleFor(Strategy $strategy): ?Rule
    {
        return $strategy->aiRun?->rule ?? $strategy->run?->rule;
    }
}
