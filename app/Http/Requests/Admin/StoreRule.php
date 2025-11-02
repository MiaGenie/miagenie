<?php

namespace App\Http\Requests\Admin;

use App\Enums\RuleType;
use App\Models\Rule;
use App\Models\Version;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule as ValidationRule;

class StoreRule extends FormRequest
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
     * @return Rule
     */
    public function handle(): Rule
    {
        $version = Version::firstOrFailByUuid($this->route('version'));

        return Rule::create([
            'version_id' => $version->id,
            'rule_type' => $this->input('rule_type'),
            'link_upstream' => $this->input('link_upstream'),
            'name' => $this->input('name'),
            'description' => $this->input('description'),
            'status' => $this->input('status'),
        ]);
    }
}
