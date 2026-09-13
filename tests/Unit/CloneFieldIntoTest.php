<?php

namespace Tests\Unit;

use App\Enums\SubFieldType;
use App\Http\Requests\Admin\CloneFieldInto;
use App\Models\VersionFieldSubField;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class CloneFieldIntoTest extends TestCase
{
    /**
     * The ordering helper only reads the tree it is handed, so it can be exercised on unsaved models.
     */
    protected function request(): CloneFieldInto
    {
        return new class extends CloneFieldInto
        {
            /**
             * @param  Collection<int, VersionFieldSubField>  $subFields
             * @return array<int, int>
             */
            public function order(Collection $subFields): array
            {
                return array_map(
                    fn (VersionFieldSubField $subField) => $subField->id,
                    $this->orderedParentsFirst($subFields)
                );
            }
        };
    }

    protected function subField(int $id, ?int $parentId, int $position): VersionFieldSubField
    {
        $subField = new VersionFieldSubField([
            'uuid' => "uuid-$id",
            'parent_id' => $parentId,
            'name' => ['en-GB' => "field-$id"],
            'sub_code_name' => "field_$id",
            'type' => SubFieldType::STRING,
            'position' => $position,
        ]);

        $subField->id = $id;

        return $subField;
    }

    public function test_every_parent_precedes_its_children(): void
    {
        // Given flat and shuffled, so a naive pass would meet children before their parents.
        $tree = new Collection([
            $this->subField(5, 2, 0),
            $this->subField(2, 1, 1),
            $this->subField(4, 1, 0),
            $this->subField(1, null, 1),
            $this->subField(3, null, 0),
            $this->subField(6, 5, 0),
        ]);

        $ordered = $this->request()->order($tree);

        $this->assertSame([3, 1, 4, 2, 5, 6], $ordered);
    }

    public function test_parent_ids_handed_back_as_strings_still_match(): void
    {
        $child = $this->subField(2, null, 0);
        $child->parent_id = '1';

        $tree = new Collection([$child, $this->subField(1, null, 0)]);

        $this->assertSame([1, 2], $this->request()->order($tree));
    }

    public function test_orphans_are_dropped_rather_than_saved_with_a_dangling_parent(): void
    {
        $tree = new Collection([
            $this->subField(1, null, 0),
            $this->subField(9, 99, 0),
        ]);

        $this->assertSame([1], $this->request()->order($tree));
    }
}
