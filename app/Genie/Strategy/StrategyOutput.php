<?php

namespace App\Genie\Strategy;

use App\Enums\RuleSubType;
use App\Models\AiRunStep;
use RuntimeException;

/**
 * Maps a step's structured response onto the strategy fields it declares in `output`.
 */
class StrategyOutput
{
    /**
     * @param  array<string, mixed>  $structured
     */
    public function write(AiRunStep $runStep, array $structured): void
    {
        $strategy = $runStep->run->strategy;

        if (! $strategy) {
            throw new RuntimeException("Run [{$runStep->run_id}] has no strategy to write to.");
        }

        $strategy->update([
            'content' => array_merge($strategy->content ?? [], $this->resolve($runStep, $structured)),
        ]);
    }

    /**
     * A multi-output step writes every key it declared; any other writes one.
     *
     * @param  array<string, mixed>  $structured
     * @return array<string, mixed>
     */
    public function resolve(AiRunStep $runStep, array $structured): array
    {
        $step = $runStep->step;
        $outputs = $step->output ?? [];

        if ($outputs === []) {
            throw new RuntimeException("Step [{$step->id}] declares no output field.");
        }

        if ($step->rule_sub_type === RuleSubType::BRIEFINGS_MULTIPLE) {
            $content = [];

            foreach ($outputs as $codeName) {
                $content[$codeName] = $structured[$codeName] ?? null;
            }

            return $content;
        }

        $codeName = $outputs[0];

        return [$codeName => $structured[$codeName] ?? null];
    }
}
