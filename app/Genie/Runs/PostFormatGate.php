<?php

namespace App\Genie\Runs;

use App\Enums\PostFormat;
use App\Models\AiRun;
use App\Models\RuleStep;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Decides whether a step applies to a run that was asked for a post format.
 *
 * A rule for ideas — and later for posts — has one generator step per format, each naming its
 * own `post_format`. The run's `parameters` say which format was asked for: a specific one runs
 * only the step that matches, none runs them all. A context step has no format and always runs.
 */
class PostFormatGate implements StepGate
{
    public const PARAMETER = 'post_format';

    public function passes(AiRun $run, RuleStep $step): bool
    {
        $subType = $step->rule_sub_type;

        if ($subType?->isContext()) {
            return true;
        }

        if (! $subType?->isGenerator()) {
            return $this->skip($step, 'its sub type does not generate for a post format');
        }

        if (! $step->post_format) {
            return $this->skip($step, 'it names no post format');
        }

        return $this->matches(self::requested($run), $step->post_format);
    }

    /**
     * Whether a step's format is the one the run asked for; no request means every format.
     */
    public function matches(?PostFormat $requested, ?PostFormat $stepFormat): bool
    {
        if (! $stepFormat) {
            return false;
        }

        return $requested === null || $requested === $stepFormat;
    }

    /**
     * The generator steps of the run's rule that this gate lets through, in rule order.
     *
     * Read before the run starts to know how many steps will produce output — how a total is
     * split between them, and whether the request can be served at all.
     *
     * @return Collection<int, RuleStep>
     */
    public function generators(AiRun $run): Collection
    {
        $steps = $run->rule?->steps ?? new Collection;

        return $steps
            ->filter(fn (RuleStep $step) => $step->rule_sub_type?->isGenerator() && $this->passes($run, $step))
            ->sortBy('position')
            ->values();
    }

    /**
     * The format the run was asked for, or null for any.
     */
    public static function requested(AiRun $run): ?PostFormat
    {
        $value = $run->parameters[self::PARAMETER] ?? null;

        return is_numeric($value) ? PostFormat::tryFrom((int) $value) : null;
    }

    /**
     * A step that cannot be matched is a misconfigured rule, not a broken run, so it is passed
     * over and the reason recorded.
     */
    protected function skip(RuleStep $step, string $reason): bool
    {
        Log::warning('Genie format step skipped: '.$reason, [
            'step_id' => $step->id,
            'rule_sub_type' => $step->rule_sub_type?->name,
            'post_format' => $step->post_format?->name,
        ]);

        return false;
    }
}
