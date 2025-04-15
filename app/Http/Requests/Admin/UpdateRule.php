<?php

namespace App\Http\Requests\Admin;

use App\Enums\RuleType;
use App\Models\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule as ValidationRule;

class UpdateRule extends FormRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'rule_type' => [ValidationRule::enum(RuleType::class)],
            'rule_sub_type' => [ValidationRule::enum(RuleSubType::class)],
        ];
    }

    /**
     * @return int
     */
    public function handle(): int
    {
        $record = Rule::firstOrFailByUuid($this->route('rule'));

        return $record->update([
            'version_id' => $this->input('version_id'),
            'rule_type' => $this->input('rule_type'),
            'rule_sub_type' => $this->input('rule_sub_type'),
            'name' => $this->input('name'),
            'description' => $this->input('description'),
            'status' => $this->input('status')
        ]);
    }

}
