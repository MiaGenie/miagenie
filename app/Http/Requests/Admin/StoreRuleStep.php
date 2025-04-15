<?php

namespace App\Http\Requests\Admin;

use App\Models\Rule;
use App\Models\RuleStep;
use Illuminate\Foundation\Http\FormRequest;

class StoreRuleStep extends FormRequest
{

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'assistant_id' => ['required', 'integer'],
            'message' => ['required', 'string'],
            'output' => ['required', 'string']
        ];
    }

    public function handle(): RuleStep
    {
        $rule = Rule::firstOrFailByUuid($this->route('rule'));

        $position = RuleStep::where('rule_id', $this->input('rule_id'))->max('position') + 1;

        return RuleStep::create([
            'rule_id' => $rule->id,
            'name' => $this->input('name'),
            'description' => $this->input('description'),
            'assistant_id' => $this->input('assistant_id'),
            'message' => $this->input('message'),
            'output' => $this->input('output'),
            'position' => $position
        ]);
    }
}
