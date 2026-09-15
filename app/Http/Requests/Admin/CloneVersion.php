<?php

namespace App\Http\Requests\Admin;

use App\Concerns\Requests\ClonesSubFields;
use App\Enums\VersionStatus;
use App\Models\RuleStep;
use App\Models\Version;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class CloneVersion extends FormRequest
{
    use ClonesSubFields;

    /**
     * Source field id => cloned field id, so steps can follow their dependency into the clone.
     *
     * @var array<int, int>
     */
    protected array $fieldIdMap = [];

    /**
     * Source sub-field id => cloned sub-field id, across every field of the version.
     *
     * @var array<int, int>
     */
    protected array $subFieldIdMap = [];

    /**
     * @throws \Throwable
     */
    public function handle(): Version
    {
        $version = Version::with(['fields.options', 'fields.subFields', 'rules.steps'])
            ->where('uuid', $this->route('version'))
            ->firstOrFail();

        $newVersion = $version->replicate(['uuid']);
        $newVersion->name .= ' (Cloned)';
        $newVersion->status = VersionStatus::DISABLED;
        $newVersion->is_default = false;

        $this->fieldIdMap = [];
        $this->subFieldIdMap = [];

        return DB::transaction(function () use ($version, $newVersion) {
            $newVersion->save();

            foreach ($version->fields as $field) {
                $newField = $field->replicate(['uuid']);
                $newField->version_id = $newVersion->id;
                $newField->save();

                $this->fieldIdMap[$field->id] = $newField->id;

                foreach ($field->options as $option) {
                    $newOption = $option->replicate(['uuid']);
                    $newOption->field_id = $newField->id;
                    $newOption->save();
                }

                $this->subFieldIdMap += $this->cloneSubFields($field->subFields, $newField);
            }

            foreach ($version->rules as $rule) {
                $newRule = $rule->replicate(['uuid']);
                $newRule->version_id = $newVersion->id;
                $newRule->save();

                foreach ($rule->steps as $step) {
                    $newStep = $step->replicate(['uuid']);
                    $newStep->rule_id = $newRule->id;
                    [$newStep->depends_on_field, $newStep->depends_on_option] = $this->remapDependency($step);
                    $newStep->save();
                }
            }

            return $newVersion;
        });
    }

    /**
     * `depends_on_field` and `depends_on_option` are ids on the source version, so they are
     * translated through the ids the copies were given. A dependency that points outside the
     * version is dropped rather than left on the source's rows.
     *
     * @return array{0: int|null, 1: int|null}
     */
    protected function remapDependency(RuleStep $step): array
    {
        $field = $step->depends_on_field === null
            ? null
            : $this->fieldIdMap[(int) $step->depends_on_field] ?? null;

        if ($field === null) {
            return [null, null];
        }

        $option = $step->depends_on_option === null
            ? null
            : $this->subFieldIdMap[(int) $step->depends_on_option] ?? null;

        return [$field, $option];
    }
}
