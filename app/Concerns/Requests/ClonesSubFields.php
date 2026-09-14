<?php

namespace App\Concerns\Requests;

use App\Models\VersionField;
use App\Models\VersionFieldSubField;
use Illuminate\Database\Eloquent\Collection;

trait ClonesSubFields
{
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
