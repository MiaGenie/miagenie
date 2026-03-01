<?php

namespace App\Http\Requests\Admin;

use App\Enums\RuleStatus;
use App\Models\Version;
use Illuminate\Foundation\Http\FormRequest;

use App\Models\Rule;

class CloneRuleInto extends FormRequest
{

    /**
     * @return Rule
     */
    public function handle(): Rule
    {
        $version = Version::firstOrFailByUuid($this->route('version'));
        $rule = Rule::firstOrFailByUuid($this->route('rule'));
        $target = Version::findByUuid($this->route('target'));

        $newRule = $rule->replicate(['uuid']);
        $newRule->version_id = $target->id;
        $newRule->name = $version . ' - ' . $newRule->name . ' (Cloned)';
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
