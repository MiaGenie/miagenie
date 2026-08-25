<?php

namespace App\Genie\Strategy;

use App\Enums\RuleSubType;
use App\Models\AiRun;
use App\Models\RuleStep;
use Illuminate\Support\Facades\Log;

/**
 * Decides whether a step applies to this run.
 *
 * Only a CHANNELS step has anything to decide: it belongs to one channel, and that channel is
 * chosen earlier in the run. The step names the linkable strategy field that holds the choice
 * (`depends_on_field`) and the boolean sub-field standing for its own channel
 * (`depends_on_option`), so the answer is read straight out of the strategy.
 */
class StrategyChannelGate
{
    /**
     * The strategy is read rather than the step that produced it: a reviewer can change the
     * selection before the run moves on, and their answer is the one that counts.
     */
    public function passes(AiRun $run, RuleStep $step): bool
    {
        if ($step->rule_sub_type !== RuleSubType::CHANNELS) {
            return true;
        }

        $field = $step->dependsOnField;
        $subField = $step->dependsOnOption;

        if (! $field || ! $subField) {
            return $this->skip($step, 'the step names no field or no sub-field to depend on');
        }

        $content = $run->strategy?->content ?? [];

        if (! array_key_exists($field->code_name, $content)) {
            return $this->skip($step, "the strategy holds no [{$field->code_name}] yet");
        }

        return $this->selects($content[$field->code_name], $subField->sub_code_name);
    }

    /**
     * Whether the stored answer marks this sub-field as chosen.
     *
     * Only boolean sub-fields can be depended on, and a strategy keeps them as a flat map of
     * `sub_code_name => bool` under the field's own code name, so this is a direct read: an
     * absent key is an unchosen channel.
     */
    public function selects(mixed $value, string $subCodeName): bool
    {
        if (! is_array($value) || ! array_key_exists($subCodeName, $value)) {
            return false;
        }

        $chosen = $value[$subCodeName];

        // A JSON "false" is a string, and every non-empty string is truthy.
        return is_string($chosen)
            ? filter_var($chosen, FILTER_VALIDATE_BOOLEAN)
            : (bool) $chosen;
    }

    /**
     * A dependency that cannot be resolved is a misconfigured rule, not a broken run, so the step
     * is passed over and the reason recorded.
     */
    protected function skip(RuleStep $step, string $reason): bool
    {
        Log::warning('Genie channel step skipped: '.$reason, [
            'step_id' => $step->id,
            'depends_on_field' => $step->depends_on_field,
            'depends_on_option' => $step->depends_on_option,
        ]);

        return false;
    }
}
