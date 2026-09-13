<?php

namespace App\Http\Requests\Admin;

use App\Models\Rule;
use App\Models\RuleStep;
use App\Models\VersionField;
use App\Models\VersionFieldSubField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CloneStepInto extends FormRequest
{
    /**
     * Copy a step to the end of another rule of the same type, on any version.
     *
     * @throws ValidationException
     * @throws \Throwable
     */
    public function handle(): RuleStep
    {
        $rule = Rule::firstOrFailByUuid($this->route('rule'));
        $step = RuleStep::firstOrFailByUuid($this->route('step'));
        $target = Rule::firstOrFailByUuid($this->route('target'));

        if ($target->id === $rule->id || $target->rule_type !== $rule->rule_type) {
            throw ValidationException::withMessages([
                'target' => __('genie.clone_step_into_type_mismatch'),
            ]);
        }

        $newStep = $step->replicate(['uuid']);
        $newStep->rule_id = $target->id;
        $newStep->position = RuleStep::where('rule_id', $target->id)->max('position') + 1;

        if ($target->version_id !== $rule->version_id) {
            [$newStep->depends_on_field, $newStep->depends_on_option] = $this->remapDependencies($step, $target);
        }

        return DB::transaction(function () use ($newStep) {
            $newStep->save();

            return $newStep;
        });
    }

    /**
     * `depends_on_field` and `depends_on_option` are ids of the source version's field and
     * sub-field, so on another version they are matched by code name instead.
     *
     * @return array{0: int|null, 1: int|null}
     *
     * @throws ValidationException
     */
    protected function remapDependencies(RuleStep $step, Rule $target): array
    {
        if (blank($step->depends_on_field)) {
            return [null, null];
        }

        $sourceField = VersionField::find($step->depends_on_field);

        $targetField = $sourceField
            ? VersionField::where('version_id', $target->version_id)
                ->where('group_type', $sourceField->group_type)
                ->where('code_name', $sourceField->code_name)
                ->first()
            : null;

        if (! $targetField) {
            throw ValidationException::withMessages([
                'target' => __('genie.clone_step_into_dependency_missing'),
            ]);
        }

        if (blank($step->depends_on_option)) {
            return [$targetField->id, null];
        }

        $sourceOption = VersionFieldSubField::find($step->depends_on_option);

        $targetOption = $sourceOption
            ? $targetField->subFields()
                ->where('sub_code_name', $sourceOption->sub_code_name)
                ->first()
            : null;

        if (! $targetOption) {
            throw ValidationException::withMessages([
                'target' => __('genie.clone_step_into_dependency_missing'),
            ]);
        }

        return [$targetField->id, $targetOption->id];
    }
}
