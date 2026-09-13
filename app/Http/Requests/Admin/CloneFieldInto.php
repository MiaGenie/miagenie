<?php

namespace App\Http\Requests\Admin;

use App\Models\Version;
use App\Models\VersionField;
use App\Models\VersionFieldSubField;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CloneFieldInto extends FormRequest
{
    /**
     * Copy a field, with its options and sub-field tree, to the end of its group on another version.
     *
     * @throws ValidationException
     * @throws \Throwable
     */
    public function handle(): VersionField
    {
        $field = VersionField::with(['options', 'subFields'])
            ->where('uuid', $this->route('field'))
            ->firstOrFail();
        $target = Version::firstOrFailByUuid($this->route('target'));

        $targetGroup = VersionField::where('version_id', $target->id)
            ->where('group_type', $field->group_type);

        if ((clone $targetGroup)->where('code_name', $field->code_name)->exists()) {
            throw ValidationException::withMessages([
                'target' => __('genie.clone_field_into_code_name_exists'),
            ]);
        }

        $newField = $field->replicate(['uuid']);
        $newField->version_id = $target->id;
        $newField->position = (clone $targetGroup)->max('position') + 1;

        if ($newField->is_identifier && (clone $targetGroup)->where('is_identifier', true)->exists()) {
            $newField->is_identifier = false;
        }

        return DB::transaction(function () use ($field, $newField) {
            $newField->save();

            foreach ($field->options as $option) {
                $newOption = $option->replicate(['uuid']);
                $newOption->field_id = $newField->id;
                $newOption->save();
            }

            $this->cloneSubFields($field->subFields, $newField);

            return $newField;
        });
    }

    /**
     * Sub-fields point at their parent by id, so parents are written before their children
     * and every `parent_id` is translated through the ids the copies were given.
     *
     * @param  Collection<int, VersionFieldSubField>  $subFields
     * @return array<int, int> source id => cloned id
     */
    protected function cloneSubFields(Collection $subFields, VersionField $newField): array
    {
        $idMap = [];

        foreach ($this->orderedParentsFirst($subFields) as $subField) {
            $newSubField = $subField->replicate(['uuid']);
            $newSubField->field_id = $newField->id;
            $newSubField->parent_id = $subField->parent_id === null
                ? null
                : $idMap[(int) $subField->parent_id];
            $newSubField->save();

            $idMap[$subField->id] = $newSubField->id;
        }

        return $idMap;
    }

    /**
     * Flatten the tree depth first from its roots, so any node comes after its parent.
     *
     * @param  Collection<int, VersionFieldSubField>  $subFields
     * @return array<int, VersionFieldSubField>
     */
    protected function orderedParentsFirst(Collection $subFields, ?int $parentId = null): array
    {
        $ordered = [];

        $children = $subFields
            ->filter(fn (VersionFieldSubField $subField) => $this->intOrNull($subField->parent_id) === $parentId)
            ->sortBy('position');

        foreach ($children as $child) {
            $ordered[] = $child;
            array_push($ordered, ...$this->orderedParentsFirst($subFields, $child->id));
        }

        return $ordered;
    }

    protected function intOrNull(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }
}
