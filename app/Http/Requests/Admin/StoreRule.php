<?php

namespace App\Http\Requests\Admin;

use App\Enums\RuleSubType;
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
            'rule_sub_type' => [ValidationRule::enum(RuleSubType::class)],
        ];
    }

    /**
     * @return Rule
     */
    public function handle(): Rule
    {
        $version = Version::firstOrFailByUuid($this->route('version'));

        $position = Rule::where('version_id', $version->id)->max('position') + 1;

        return Rule::create([
            'version_id' => $version->id,
            'rule_type' => $this->input('rule_type'),
            'rule_sub_type' => $this->input('rule_sub_type'),
            'name' => $this->input('name'),
            'description' => $this->input('description'),
            'status' => $this->input('status'),
            'position' => $position
        ]);
    }
}
