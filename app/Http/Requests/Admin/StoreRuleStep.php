<?php

namespace App\Http\Requests\Admin;

use App\Enums\RuleSubType;
use App\Models\Rule;
use App\Models\RuleStep;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule as ValidationRule;

class StoreRuleStep extends FormRequest
{

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'rule_sub_type' => [ValidationRule::enum(RuleSubType::class)],
            'name' => ['required', 'string', 'max:255'],
            'assistant_id' => ['required', 'integer'],
            'message' => ['required', 'string'],
            'output' => ['required', 'string'],
            'optional' => ['required', 'boolean'],
        ];
    }

    /**
     * @return RuleStep
     */
    public function handle(): RuleStep
    {
        $rule = Rule::firstOrFailByUuid($this->route('rule'));

        $position = RuleStep::where('rule_id', $this->input('rule_id'))->max('position') + 1;

        return RuleStep::create([
            'rule_id' => $rule->id,
            'rule_sub_type' => $this->input('rule_sub_type'),
            'name' => $this->input('name'),
            'description' => $this->input('description'),
            'assistant_id' => $this->input('assistant_id'),
            'message' => $this->input('message'),
            'output' => $this->input('output'),
            'optional' => $this->input('optional'),
            'position' => $position
        ]);
    }
}
