<?php

namespace Tests\Unit;

use App\Concerns\Requests\HandlesSubFields;
use App\Enums\SubFieldType;
use App\Models\VersionFieldSubField;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class HandlesSubFieldsTest extends TestCase
{
    /**
     * The trait's matching helpers only read the tree they are handed, so they can be exercised
     * on unsaved models.
     */
    protected function handler(): object
    {
        return new class
        {
            use HandlesSubFields;

            /**
             * @param  Collection<int, VersionFieldSubField>  $existing
             * @param  array<string, mixed>  $subField
             * @param  array<int, string>  $claimedUuids
             * @param  array<int, int>  $keptIds
             */
            public function match(
                Collection $existing,
                array $subField,
                ?int $parentId,
                array $claimedUuids = [],
                array $keptIds = []
            ): ?VersionFieldSubField {
                return $this->matchExistingSubField($existing, $subField, $parentId, $claimedUuids, $keptIds);
            }

            /**
             * @param  array<int, array<string, mixed>>  $subFields
             * @return array<int, string>
             */
            public function uuids(array $subFields): array
            {
                return $this->postedUuids($subFields);
            }
        };
    }

    protected function subField(int $id, string $codeName, SubFieldType $type, ?int $parentId = null): VersionFieldSubField
    {
        $subField = new VersionFieldSubField([
            'uuid' => "uuid-$id",
            'parent_id' => $parentId,
            'name' => ['en-GB' => $codeName],
            'sub_code_name' => $codeName,
            'type' => $type,
        ]);

        $subField->id = $id;

        return $subField;
    }

    /**
     * @param  array<int, VersionFieldSubField>  $subFields
     * @return Collection<int, VersionFieldSubField>
     */
    protected function tree(array $subFields): Collection
    {
        return new Collection($subFields);
    }

    /**
     * @return array<string, mixed>
     */
    protected function posted(string $codeName, SubFieldType $type): array
    {
        return [
            'id' => null,
            'sub_code_name' => $codeName,
            'type' => $type->value,
        ];
    }

    public function test_a_re_added_sub_field_matches_the_sibling_of_the_same_shape(): void
    {
        $existing = $this->tree([$this->subField(1, 'pillars', SubFieldType::STRING)]);

        $match = $this->handler()->match($existing, $this->posted('pillars', SubFieldType::STRING), null);

        $this->assertSame(1, $match?->id);
    }

    public function test_a_sub_field_that_came_back_as_another_type_is_not_matched(): void
    {
        $existing = $this->tree([$this->subField(1, 'pillars', SubFieldType::STRING)]);

        $this->assertNull(
            $this->handler()->match($existing, $this->posted('pillars', SubFieldType::ARRAY), null)
        );
    }

    public function test_a_row_another_node_still_points_at_is_left_alone(): void
    {
        $existing = $this->tree([$this->subField(1, 'pillars', SubFieldType::STRING)]);

        $this->assertNull(
            $this->handler()->match($existing, $this->posted('pillars', SubFieldType::STRING), null, ['uuid-1'])
        );
    }

    public function test_a_row_already_claimed_by_the_walk_is_not_taken_twice(): void
    {
        $existing = $this->tree([$this->subField(1, 'pillars', SubFieldType::STRING)]);

        $this->assertNull(
            $this->handler()->match($existing, $this->posted('pillars', SubFieldType::STRING), null, [], [1])
        );
    }

    public function test_only_siblings_of_the_same_parent_are_matched(): void
    {
        $existing = $this->tree([$this->subField(2, 'pillars', SubFieldType::STRING, 1)]);

        $handler = $this->handler();

        $this->assertNull(
            $handler->match($existing, $this->posted('pillars', SubFieldType::STRING), null)
        );

        $this->assertSame(
            2,
            $handler->match($existing, $this->posted('pillars', SubFieldType::STRING), 1)?->id
        );
    }

    public function test_every_posted_id_in_the_tree_is_collected(): void
    {
        $uuids = $this->handler()->uuids([
            [
                'id' => 'uuid-1',
                'sub_code_name' => 'pillars',
                'type' => SubFieldType::OBJECT->value,
                'children' => [
                    ['id' => null, 'sub_code_name' => 'title', 'type' => SubFieldType::STRING->value],
                    ['id' => 'uuid-3', 'sub_code_name' => 'body', 'type' => SubFieldType::STRING->value],
                ],
            ],
        ]);

        $this->assertSame(['uuid-1', 'uuid-3'], $uuids);
    }
}
