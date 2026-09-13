<?php

namespace App\Genie\Versions;

use App\Models\Rule;
use App\Models\RuleStep;
use App\Models\Version;
use App\Models\VersionField;
use App\Models\VersionFieldOption;
use App\Models\VersionFieldSubField;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Serialises a version, with its fields, options, sub-field tree, rules and steps, into a
 * portable array that `VersionImporter` can rebuild on any installation.
 *
 * No database id leaves the system: sub-fields nest under their parent, and a step's
 * dependency is written as the field's group type and code names, the same match
 * `CloneStepInto` uses across versions. Model profiles are referenced by name only.
 */
class VersionExporter
{
    public const FORMAT = 'miagenie.version';

    public const FORMAT_VERSION = 1;

    /**
     * Columns that describe a row's place in this database rather than its content.
     *
     * @var array<int, string>
     */
    protected const INTERNAL_COLUMNS = ['id', 'uuid', 'created_at', 'updated_at'];

    /**
     * @return array<string, mixed>
     */
    public function export(Version $version): array
    {
        $version->load(['fields.options', 'fields.subFields', 'rules.steps.modelProfile']);

        $steps = $version->rules->flatMap(fn (Rule $rule) => $rule->steps);

        $fieldsById = VersionField::findMany($steps->pluck('depends_on_field')->filter()->unique())->keyBy('id');
        $subFieldsById = VersionFieldSubField::findMany($steps->pluck('depends_on_option')->filter()->unique())->keyBy('id');

        return [
            'format' => self::FORMAT,
            'format_version' => self::FORMAT_VERSION,
            'exported_at' => now()->toIso8601ZuluString(),
            'version' => $this->attributesOf($version, ['status', 'is_default']),
            'fields' => $version->fields
                ->sortBy('position')
                ->values()
                ->map(fn (VersionField $field) => $this->exportField($field))
                ->all(),
            'rules' => $version->rules
                ->map(fn (Rule $rule) => $this->exportRule($rule, $fieldsById, $subFieldsById))
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function exportField(VersionField $field): array
    {
        return [
            ...$this->attributesOf($field, ['version_id']),
            'options' => $field->options
                ->map(fn (VersionFieldOption $option) => $this->attributesOf($option, ['field_id']))
                ->all(),
            'sub_fields' => $this->exportSubFields($field->subFields),
        ];
    }

    /**
     * @param  Collection<int, VersionField>  $fieldsById
     * @param  Collection<int, VersionFieldSubField>  $subFieldsById
     * @return array<string, mixed>
     */
    public function exportRule(Rule $rule, Collection $fieldsById, Collection $subFieldsById): array
    {
        return [
            ...$this->attributesOf($rule, ['version_id']),
            'steps' => $rule->steps
                ->map(fn (RuleStep $step) => $this->exportStep($step, $fieldsById, $subFieldsById))
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, VersionField>  $fieldsById
     * @param  Collection<int, VersionFieldSubField>  $subFieldsById
     * @return array<string, mixed>
     */
    public function exportStep(RuleStep $step, Collection $fieldsById, Collection $subFieldsById): array
    {
        return [
            ...$this->attributesOf($step, ['rule_id', 'model_profile_id', 'depends_on_field', 'depends_on_option']),
            'model_profile' => $step->modelProfile?->name,
            'depends_on' => $this->dependsOn($step, $fieldsById, $subFieldsById),
        ];
    }

    /**
     * The step's dependency by code name, or null when the step has none or the field it
     * pointed at no longer exists.
     *
     * @param  Collection<int, VersionField>  $fieldsById
     * @param  Collection<int, VersionFieldSubField>  $subFieldsById
     * @return array{group_type: int, code_name: string, sub_code_name: string|null}|null
     */
    public function dependsOn(RuleStep $step, Collection $fieldsById, Collection $subFieldsById): ?array
    {
        $field = blank($step->depends_on_field) ? null : $fieldsById->get((int) $step->depends_on_field);

        if (! $field) {
            return null;
        }

        $subField = blank($step->depends_on_option) ? null : $subFieldsById->get((int) $step->depends_on_option);

        return [
            'group_type' => $field->group_type->value,
            'code_name' => $field->code_name,
            'sub_code_name' => $subField?->sub_code_name,
        ];
    }

    /**
     * Nest the flat sub-field rows under their parents, siblings in position order.
     *
     * @param  Collection<int, VersionFieldSubField>  $subFields
     * @return array<int, array<string, mixed>>
     */
    public function exportSubFields(Collection $subFields, ?int $parentId = null): array
    {
        return $subFields
            ->filter(fn (VersionFieldSubField $subField) => $this->intOrNull($subField->parent_id) === $parentId)
            ->sortBy('position')
            ->values()
            ->map(fn (VersionFieldSubField $subField) => [
                ...$this->attributesOf($subField, ['field_id', 'parent_id']),
                'children' => $this->exportSubFields($subFields, $subField->id),
            ])
            ->all();
    }

    /**
     * The row's own columns, run through its casts so enums become their backing values and
     * JSON columns decode. `attributesToArray()` is used on purpose: the app's
     * `HasTranslations::toArray()` collapses translations to the current locale, whereas the
     * export must carry every locale.
     *
     * @param  array<int, string>  $except
     * @return array<string, mixed>
     */
    protected function attributesOf(Model $model, array $except = []): array
    {
        return Arr::except($model->attributesToArray(), [...self::INTERNAL_COLUMNS, ...$except]);
    }

    protected function intOrNull(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }
}
