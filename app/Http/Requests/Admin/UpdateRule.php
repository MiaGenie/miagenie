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
            'link_upstream' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return int
     */
    public function handle(): int
    {
        $record = Rule::firstOrFailByUuid($this->route('rule'));

        return $record->update([
            'link_upstream' => $this->input('link_upstream'),
            'name' => $this->input('name'),
            'description' => $this->input('description'),
            'status' => $this->input('status')
        ]);
    }

}
