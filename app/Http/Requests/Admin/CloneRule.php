<?php

namespace App\Http\Requests\Admin;

use App\Enums\RuleStatus;
use Illuminate\Foundation\Http\FormRequest;

use App\Models\Rule;

class CloneRule extends FormRequest
{

    /**
     * @return Rule
     */
    public function handle(): Rule
    {
        $rule = Rule::firstOrFailByUuid($this->route('rule'));

        $newRule = $rule->replicate(['uuid']);
        $newRule->name .= ' (Cloned)';
        $newRule->status = RuleStatus::DISABLED;
        $newRule->save();

        foreach ($newRule->steps as $step) {
            $newStep = $step->replicate(['uuid']);
            $newStep->rule_id = $newRule->id;
            $newStep->save();
        }

        return $newRule;
    }
}
