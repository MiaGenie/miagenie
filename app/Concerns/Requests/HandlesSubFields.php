<?php

namespace App\Concerns\Requests;

use App\Enums\SubFieldType;
use App\Enums\VersionGroupType;
use App\Models\VersionField;
use App\Models\VersionFieldSubField;
use App\Rules\SnakeCase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;

trait HandlesSubFields
{
    /**
     * Validation rules for the sub-field tree.
     *
     * The tree arrives nested, so the wildcards have to cover every depth the admin can build.
     * Five levels is well beyond anything a strategy field needs in practice.
     *
     * @return array<string, mixed>
     */
    protected function subFieldRules(int $depth = 5): array
    {
        $rules = ['sub_fields' => ['nullable', 'array']];

        $prefix = 'sub_fields.*';

        for ($level = 0; $level < $depth; $level++) {
            $rules[$prefix.'.name'] = ['required', 'string', 'max:500'];
            $rules[$prefix.'.sub_code_name'] = ['required', new SnakeCase, 'max:255'];
            $rules[$prefix.'.description'] = ['nullable', 'string', 'max:5000'];
            $rules[$prefix.'.type'] = ['required', Rule::enum(SubFieldType::class)];
            $rules[$prefix.'.min_length'] = ['nullable', 'integer', 'min:0'];
            $rules[$prefix.'.max_length'] = ['nullable', 'integer', 'min:0'];
            $rules[$prefix.'.pattern'] = ['nullable', 'string', 'max:255'];
            $rules[$prefix.'.min_items'] = ['nullable', 'integer', 'min:0'];
            $rules[$prefix.'.max_items'] = ['nullable', 'integer', 'min:0'];
            $rules[$prefix.'.required'] = ['nullable', 'boolean'];
            $rules[$prefix.'.editable'] = ['nullable', 'boolean'];
            $rules[$prefix.'.enum_values'] = ['nullable', 'array'];
            $rules[$prefix.'.icon'] = ['nullable', 'string', 'max:255'];
            $rules[$prefix.'.class'] = ['nullable', 'string', 'max:255'];
            $rules[$prefix.'.block'] = ['nullable', 'string', 'max:255'];
            $rules[$prefix.'.children'] = ['nullable', 'array'];

            $prefix .= '.children.*';
        }

        return $rules;
    }

    /**
     * Sibling sub-fields become sibling keys in the generated JSON Schema, so a duplicate
     * would silently overwrite its twin. Laravel's `distinct` rule compares every value at a
     * wildcard depth rather than per parent, which would wrongly reject two different objects
     * that each legitimately contain a "name" child, so the check is scoped by hand.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->assertUniqueSiblings(
                (array) $this->input('sub_fields', []),
                'sub_fields',
                $validator
            );
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $subFields
     */
    protected function assertUniqueSiblings(array $subFields, string $path, $validator): void
    {
        $seen = [];

        foreach (array_values($subFields) as $index => $subField) {
            $codeName = $subField['sub_code_name'] ?? null;

            if ($codeName !== null && isset($seen[$codeName])) {
                $validator->errors()->add(
                    "$path.$index.sub_code_name",
                    __('genie.sub_field_code_name_duplicate', ['sub_code_name' => $codeName])
                );
            }

            $seen[$codeName] = true;

            $this->assertUniqueSiblings(
                (array) ($subField['children'] ?? []),
                "$path.$index.children",
                $validator
            );
        }
    }

    /**
     * Persist the sub-field tree, then drop anything the admin removed.
     *
     * Only strategy fields carry sub-fields; for any other group the tree is cleared so a
     * field that changes group cannot keep an orphaned structure.
     */
    protected function handleSubFields(VersionField $record): void
    {
        if ((int) $this->input('group_type') !== VersionGroupType::STRATEGIES->value) {
            $record->subFields()->delete();

            return;
        }

        $subFields = (array) $this->input('sub_fields', []);

        $keptIds = [];

        $this->saveSubFields(
            $record,
            $subFields,
            null,
            $record->subFields()->get(),
            $this->postedUuids($subFields),
            $keptIds
        );

        $removed = $record->subFields()->whereNotIn('id', $keptIds ?: [0])->pluck('id')->all();

        if ($removed !== []) {
            VersionFieldSubField::destroy($removed);
        }
    }

    /**
     * Editability is a property of the sub-field's shape, so the answer is recomputed here
     * rather than trusted: a form that hid the switch still posts whatever it last held, and
     * a type or enum can change in the same request that sets it.
     *
     * @param  array<string, mixed>  $subField
     */
    protected function resolveEditable(array $subField): bool
    {
        $type = SubFieldType::from((int) $subField['type']);

        $allowed = VersionFieldSubField::allowsEditing(
            $type,
            $type->hasChildren() && filled($subField['children'] ?? []),
            filled($subField['enum_values'] ?? []),
        );

        return $allowed && (bool) ($subField['editable'] ?? true);
    }

    /**
     * Every sub-field id the payload still points at.
     *
     * A row named here belongs to the node that carries its id, so the code name fallback must
     * leave it alone even when a re-added sibling happens to describe the same shape.
     *
     * @param  array<int, array<string, mixed>>  $subFields
     * @return array<int, string>
     */
    protected function postedUuids(array $subFields): array
    {
        $uuids = [];

        foreach ($subFields as $subField) {
            if (filled($subField['id'] ?? null)) {
                $uuids[] = $subField['id'];
            }

            $uuids = array_merge($uuids, $this->postedUuids((array) ($subField['children'] ?? [])));
        }

        return $uuids;
    }

    /**
     * The row a posted sub-field should be written to.
     *
     * A node the admin removed and added again comes back without an id, so it would otherwise
     * be written as a new row and the original destroyed, taking its id with it. Matching a
     * sibling of the same shape instead keeps whatever points at that id, a rule step's
     * `depends_on_option` above all.
     *
     * @param  Collection<int, VersionFieldSubField>  $existing
     * @param  array<string, mixed>  $subField
     * @param  array<int, string>  $claimedUuids
     * @param  array<int, int>  $keptIds
     */
    protected function resolveSubFieldUuid(
        Collection $existing,
        array $subField,
        ?int $parentId,
        array $claimedUuids,
        array $keptIds
    ): ?string {
        if (filled($subField['id'] ?? null)) {
            return $subField['id'];
        }

        return $this->matchExistingSubField($existing, $subField, $parentId, $claimedUuids, $keptIds)?->uuid;
    }

    /**
     * The sibling that already describes this sub-field, if there is one.
     *
     * The code name alone is not enough: a sub-field that comes back as a different type is a
     * different shape, so it is replaced rather than reused.
     *
     * @param  Collection<int, VersionFieldSubField>  $existing
     * @param  array<string, mixed>  $subField
     * @param  array<int, string>  $claimedUuids
     * @param  array<int, int>  $keptIds
     */
    protected function matchExistingSubField(
        Collection $existing,
        array $subField,
        ?int $parentId,
        array $claimedUuids,
        array $keptIds
    ): ?VersionFieldSubField {
        $type = SubFieldType::from((int) $subField['type']);

        return $existing->first(
            fn (VersionFieldSubField $candidate) => $this->intOrNull($candidate->parent_id) === $parentId
                && $candidate->sub_code_name === ($subField['sub_code_name'] ?? null)
                && $candidate->type === $type
                && ! in_array($candidate->uuid, $claimedUuids, true)
                && ! in_array($candidate->id, $keptIds, true)
        );
    }

    /**
     * The parent key is not cast on the model, so a driver that hands back strings would break
     * an identity comparison against the id the walk carries.
     */
    protected function intOrNull(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    /**
     * Walk the tree depth first, creating parents before their children so the real parent id
     * is known by the time the children are written.
     *
     * @param  array<int, array<string, mixed>>  $subFields
     * @param  Collection<int, VersionFieldSubField>  $existing
     * @param  array<int, string>  $claimedUuids
     * @param  array<int, int>  $keptIds
     */
    protected function saveSubFields(
        VersionField $record,
        array $subFields,
        ?int $parentId,
        Collection $existing,
        array $claimedUuids,
        array &$keptIds
    ): void {
        foreach (array_values($subFields) as $position => $subField) {
            $model = $record->subFields()->updateOrCreate(
                ['uuid' => $this->resolveSubFieldUuid($existing, $subField, $parentId, $claimedUuids, $keptIds)],
                [
                    'parent_id' => $parentId,
                    'name' => $subField['name'],
                    'sub_code_name' => $subField['sub_code_name'],
                    'description' => $subField['description'] ?? null,
                    'type' => $subField['type'],
                    'min_length' => $subField['min_length'] ?? null,
                    'max_length' => $subField['max_length'] ?? null,
                    'pattern' => $subField['pattern'] ?? null,
                    'min_items' => $subField['min_items'] ?? null,
                    'max_items' => $subField['max_items'] ?? null,
                    'required' => $subField['required'] ?? true,
                    'editable' => $this->resolveEditable($subField),
                    'enum_values' => $subField['enum_values'] ?? null,
                    'icon' => $subField['icon'] ?? null,
                    'class' => $subField['class'] ?? null,
                    'block' => $subField['block'] ?? null,
                    'position' => $position,
                ]
            );

            $keptIds[] = $model->id;

            $children = SubFieldType::from((int) $subField['type'])->hasChildren()
                ? (array) ($subField['children'] ?? [])
                : [];

            $this->saveSubFields($record, $children, $model->id, $existing, $claimedUuids, $keptIds);
        }
    }
}
