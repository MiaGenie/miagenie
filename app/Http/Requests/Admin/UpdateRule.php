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
        ];
    }

    /**
     * @return int
     */
    public function handle(): int
    {
        $record = Rule::firstOrFailByUuid($this->route('rule'));

        return $record->update([
            'name' => $this->input('name'),
            'description' => $this->input('description'),
            'status' => $this->input('status')
        ]);
    }

}
