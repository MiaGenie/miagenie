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
            'instructions' => ['required'],
            'ai_model' => ['required'],
            'response_format' => ['required'],
            'json_schema' => [ValidationRule::when($this->input('response_format') === 'json_schema', ['required', 'json'])],
            'message' => ['required', 'string'],
            'output' => ['required'],
            'requires_review' => ['required', 'boolean'],
            'review_message_user' => [ValidationRule::requiredIf($this->input('requires_review'))],
            'optional' => ['required', 'boolean'],
        ];
    }

    /**
     * @return RuleStep
     */
    public function handle(): RuleStep
    {
        $rule = Rule::firstOrFailByUuid($this->route('rule'));

        $position = RuleStep::where('rule_id', $rule->id)->max('position') + 1;

        return RuleStep::create([
            'rule_id' => $rule->id,
            'rule_sub_type' => $this->input('rule_sub_type'),
            'name' => $this->input('name'),
            'description' => $this->input('description'),
            'instructions' => $this->input('instructions'),
            'ai_model' => $this->input('ai_model'),
            'response_format' => $this->input('response_format'),
            'json_schema' => $this->input('response_format') === 'json_schema' ? $this->input('json_schema') : '',
            'temperature' => $this->input('temperature'),
            'top_p' => $this->input('top_p'),
            'reasoning_effort' => $this->input('reasoning_effort'),
            'vector_id' => $this->input('vector_id'),
            'message' => $this->input('message'),
            'output' => $this->input('output'),
            'requires_review' => $this->input('requires_review'),
            'review_message_user' => $this->input('review_message_user'),
            'review_message_system' => $this->input('review_message_system'),
            'optional' => $this->input('optional'),
            'position' => $position
        ]);
    }
}
