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
            'assistant_id' => ['required', 'integer'],
            'message' => ['required', 'string'],
            'output' => ['required', 'string'],
            'optional' => ['required', 'boolean'],
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
            'assistant_id' => $this->input('assistant_id'),
            'message' => $this->input('message'),
            'output' => $this->input('output'),
            'optional' => $this->input('optional')
        ]);
    }

}
