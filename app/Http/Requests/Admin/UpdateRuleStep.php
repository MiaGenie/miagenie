<?php

namespace App\Http\Requests\Admin;

use App\Enums\RuleSubType;
use App\Models\RuleStep;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule as ValidationRule;

class UpdateRuleStep extends FormRequest
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
            'depends_on' => [ValidationRule::requiredIf($this->input('rule_sub_type') === RuleSubType::CHANNELS)],
        ];
    }

    /**
     * @return int
     */
    public function handle(): int
    {
        $record = RuleStep::firstOrFailByUuid($this->route('step'));

        return $record->update([
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
            'depends_on' => $this->input('depends_on')
        ]);
    }

}
