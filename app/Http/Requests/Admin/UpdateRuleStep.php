<?php

namespace App\Http\Requests\Admin;

use App\Concerns\Requests\ValidatesStepModality;
use App\Enums\PostFormat;
use App\Enums\RuleSubType;
use App\Models\RuleStep;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule as ValidationRule;

class UpdateRuleStep extends FormRequest
{
    use ValidatesStepModality;

    public function rules(): array
    {
        return [
            'rule_sub_type' => [ValidationRule::enum(RuleSubType::class)],
            'post_format' => [
                'nullable',
                ValidationRule::enum(PostFormat::class),
                ValidationRule::requiredIf(fn () => $this->step()->rule_sub_type?->isGenerator() ?? false),
            ],
            ...$this->modalityRules(),
            'name' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:10000'],
            'model_profile_id' => ['required', 'exists:genie_model_profiles,id', $this->imageProviderRule()],
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
     * The sub type is fixed once a step exists — the form does not send it — so the stored one
     * decides whether a post format is required.
     */
    protected function step(): RuleStep
    {
        return RuleStep::firstOrFailByUuid($this->route('step'));
    }

    public function handle(): int
    {
        $record = $this->step();

        return $record->update([
            'post_format' => $this->input('post_format'),
            'name' => $this->input('name'),
            'description' => $this->input('description'),
            'instructions' => $this->input('instructions'),
            'model_profile_id' => $this->input('model_profile_id'),
            'link_upstream' => $this->input('link_upstream'),
            'message' => $this->input('message'),
            'requires_review' => $this->input('requires_review'),
            'review_message_user' => $this->input('review_message_user'),
            'review_message_system' => $this->input('review_message_system'),
            'optional' => $this->input('optional'),
            'depends_on_field' => $this->input('depends_on_field'),
            'depends_on_option' => $this->input('depends_on_option'),
            ...$this->modalityAttributes(),
        ]);
    }
}
