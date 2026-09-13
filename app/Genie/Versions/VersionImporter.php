<?php

namespace App\Genie\Versions;

use App\Enums\VersionStatus;
use App\Models\ModelProfile;
use App\Models\Rule;
use App\Models\RuleStep;
use App\Models\Version;
use App\Models\VersionField;
use App\Models\VersionFieldOption;
use App\Models\VersionFieldSubField;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Rebuilds a version from a `VersionExporter` payload.
 *
 * The import always creates a new, disabled, non-default version, in the same spirit as
 * `CloneVersion`; nothing that already exists is touched. Rows are written through
 * `create()` so casts and mutators apply on the way in exactly as they do from the admin forms.
 */
class VersionImporter
{
    public const NAME_SUFFIX = ' (Imported)';

    /**
     * Columns a payload must never dictate, whatever it carries.
     *
     * @var array<int, string>
     */
    protected const INTERNAL_COLUMNS = ['id', 'uuid', 'created_at', 'updated_at'];

    /**
     * Fields of the version being built, by `[group_type][code_name]`, so steps can resolve
     * their dependency without a query.
     *
     * @var array<int, array<string, VersionField>>
     */
    protected array $fieldsByCodeName = [];

    /**
     * Sub-fields of the version being built, by `[field id][sub_code_name]`.
     *
     * @var array<int, array<string, VersionFieldSubField>>
     */
    protected array $subFieldsByCodeName = [];

    /**
     * Model profile ids already looked up, by name; null when no profile carries the name.
     *
     * @var array<string, int|null>
     */
    protected array $modelProfileIds = [];

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws \Throwable
     */
    public function import(array $payload): Version
    {
        $this->fieldsByCodeName = [];
        $this->subFieldsByCodeName = [];

        return DB::transaction(function () use ($payload) {
            $version = Version::create([
                'name' => $this->availableName((string) Arr::get($payload, 'version.name')),
                'description' => Arr::get($payload, 'version.description'),
                'status' => VersionStatus::DISABLED,
                'is_default' => false,
            ]);

            foreach ((array) ($payload['fields'] ?? []) as $field) {
                $this->importField($version, (array) $field);
            }

            foreach ((array) ($payload['rules'] ?? []) as $rule) {
                $this->importRule($version, (array) $rule);
            }

            return $version;
        });
    }

    /**
     * The exported name, suffixed when a version already carries it so the two can be told apart.
     */
    public function availableName(string $name): string
    {
        return Version::where('name', $name)->exists() ? $name.self::NAME_SUFFIX : $name;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function importField(Version $version, array $row): VersionField
    {
        $field = VersionField::create([
            ...$this->attributesFor(new VersionField, $row, ['options', 'sub_fields']),
            'version_id' => $version->id,
        ]);

        $this->rememberField($field);

        foreach ((array) ($row['options'] ?? []) as $option) {
            VersionFieldOption::create([
                ...$this->attributesFor(new VersionFieldOption, (array) $option),
                'field_id' => $field->id,
            ]);
        }

        $this->importSubFields($field, (array) ($row['sub_fields'] ?? []));

        return $field;
    }

    /**
     * Walk the tree depth first, creating parents before their children so the real parent id
     * is known by the time the children are written, as `HandlesSubFields::saveSubFields` does.
     *
     * @param  array<int, array<string, mixed>>  $subFields
     */
    protected function importSubFields(VersionField $field, array $subFields, ?int $parentId = null): void
    {
        foreach ($subFields as $row) {
            $row = (array) $row;

            $subField = VersionFieldSubField::create([
                ...$this->attributesFor(new VersionFieldSubField, $row, ['children']),
                'field_id' => $field->id,
                'parent_id' => $parentId,
            ]);

            $this->rememberSubField($field, $subField);

            $this->importSubFields($field, (array) ($row['children'] ?? []), $subField->id);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function importRule(Version $version, array $row): Rule
    {
        $rule = Rule::create([
            ...$this->attributesFor(new Rule, $row, ['steps']),
            'version_id' => $version->id,
        ]);

        foreach ((array) ($row['steps'] ?? []) as $step) {
            $this->importStep($rule, (array) $step);
        }

        return $rule;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function importStep(Rule $rule, array $row): RuleStep
    {
        [$dependsOnField, $dependsOnOption] = $this->resolveDependency($row['depends_on'] ?? null);

        return RuleStep::create([
            ...$this->attributesFor(new RuleStep, $row, ['model_profile', 'depends_on']),
            'rule_id' => $rule->id,
            'model_profile_id' => $this->modelProfileId($row['model_profile'] ?? null),
            'depends_on_field' => $dependsOnField,
            'depends_on_option' => $dependsOnOption,
        ]);
    }

    /**
     * The ids of the imported field and sub-field a step depends on, matched by code name
     * against what this import has written so far. A dependency the file does not carry is
     * dropped rather than left pointing at a foreign id.
     *
     * @param  array{group_type?: int|string|null, code_name?: string|null, sub_code_name?: string|null}|null  $dependsOn
     * @return array{0: int|null, 1: int|null}
     */
    public function resolveDependency(?array $dependsOn): array
    {
        if (blank($dependsOn) || blank($dependsOn['code_name'] ?? null)) {
            return [null, null];
        }

        $field = $this->fieldsByCodeName[(int) ($dependsOn['group_type'] ?? 0)][$dependsOn['code_name']] ?? null;

        if (! $field) {
            return [null, null];
        }

        if (blank($dependsOn['sub_code_name'] ?? null)) {
            return [$field->id, null];
        }

        $subField = $this->subFieldsByCodeName[$field->id][$dependsOn['sub_code_name']] ?? null;

        return [$field->id, $subField?->id];
    }

    /**
     * Make a written field resolvable by group type and code name.
     */
    public function rememberField(VersionField $field): void
    {
        $this->fieldsByCodeName[$field->group_type->value][$field->code_name] = $field;
    }

    /**
     * Make a written sub-field resolvable by its field and sub code name.
     */
    public function rememberSubField(VersionField $field, VersionFieldSubField $subField): void
    {
        $this->subFieldsByCodeName[$field->id][$subField->sub_code_name] = $subField;
    }

    protected function modelProfileId(?string $name): ?int
    {
        if (blank($name)) {
            return null;
        }

        if (! array_key_exists($name, $this->modelProfileIds)) {
            $this->modelProfileIds[$name] = ModelProfile::where('name', $name)->value('id');
        }

        return $this->modelProfileIds[$name];
    }

    /**
     * The row's own columns, without the nested collections the export hangs off it nor any
     * database identity a hand-edited file might smuggle in.
     *
     * A translatable column exported as null is left out, so it stays null instead of being
     * written back as a single null translation for the current locale.
     *
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $except
     * @return array<string, mixed>
     */
    public function attributesFor(Model $model, array $row, array $except = []): array
    {
        $translatable = method_exists($model, 'getTranslatableAttributes')
            ? $model->getTranslatableAttributes()
            : [];

        $nullTranslations = array_keys(array_filter(
            Arr::only($row, $translatable),
            fn (mixed $value) => $value === null
        ));

        return Arr::except($row, [...self::INTERNAL_COLUMNS, ...$except, ...$nullTranslations]);
    }
}
