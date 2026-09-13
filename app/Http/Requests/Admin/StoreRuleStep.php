<?php

namespace App\Http\Requests\Admin;

use App\Enums\PostFormat;
use App\Enums\RuleSubType;
use App\Models\Rule;
use App\Models\RuleStep;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule as ValidationRule;

class StoreRuleStep extends FormRequest
{
    public function rules(): array
    {
        return [
            'rule_sub_type' => [ValidationRule::enum(RuleSubType::class)],
            'post_format' => [
                'nullable',
                ValidationRule::enum(PostFormat::class),
                ValidationRule::requiredIf(fn () => $this->subType()?->isGenerator() ?? false),
            ],
            'name' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:10000'],
            'model_profile_id' => ['required', 'exists:genie_model_profiles,id'],
            'response_format' => ['required'],
            'link_upstream' => ['nullable', 'boolean'],
            'message' => ['required', 'string', 'max:5000'],
            'output' => ['nullable'],
            'requires_review' => ['required', 'boolean'],
            'review_message_user' => [ValidationRule::requiredIf((bool) $this->input('requires_review'))],
            'optional' => ['required', 'boolean'],
            'depends_on_field' => [ValidationRule::requiredIf($this->input('rule_sub_type') === RuleSubType::CHANNELS)],
            'depends_on_option' => [ValidationRule::requiredIf($this->input('rule_sub_type') === RuleSubType::CHANNELS)],
        ];
    }

    /**
     * A generator step must name the format it generates for; any other has none.
     */
    protected function subType(): ?RuleSubType
    {
        $value = $this->input('rule_sub_type');

        return is_numeric($value) ? RuleSubType::tryFrom((int) $value) : null;
    }

    public function handle(): RuleStep
    {
        $rule = Rule::firstOrFailByUuid($this->route('rule'));

        $position = RuleStep::where('rule_id', $rule->id)->max('position') + 1;

        return RuleStep::create([
            'rule_id' => $rule->id,
            'rule_sub_type' => $this->input('rule_sub_type'),
            'post_format' => $this->input('post_format'),
            'name' => $this->input('name'),
            'description' => $this->input('description'),
            'instructions' => $this->input('instructions'),
            'model_profile_id' => $this->input('model_profile_id'),
            'response_format' => $this->input('response_format'),
            'link_upstream' => $this->input('link_upstream'),
            'message' => $this->input('message'),
            'output' => $this->input('output'),
            'requires_review' => $this->input('requires_review'),
            'review_message_user' => $this->input('review_message_user'),
            'review_message_system' => $this->input('review_message_system'),
            'optional' => $this->input('optional'),
            'depends_on_field' => $this->input('depends_on_field'),
            'depends_on_option' => $this->input('depends_on_option'),
            'position' => $position,
        ]);
    }
}
