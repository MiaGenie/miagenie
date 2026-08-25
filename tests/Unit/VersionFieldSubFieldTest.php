<?php

namespace Tests\Unit;

use App\Enums\SubFieldType;
use App\Models\VersionFieldSubField;
use Tests\TestCase;

class VersionFieldSubFieldTest extends TestCase
{
    public function test_a_plain_string_is_editable(): void
    {
        $this->assertTrue(
            VersionFieldSubField::allowsEditing(SubFieldType::STRING, false, false)
        );
    }

    public function test_an_enum_is_not_editable(): void
    {
        $this->assertFalse(
            VersionFieldSubField::allowsEditing(SubFieldType::STRING, false, true)
        );
    }

    public function test_an_object_is_never_editable(): void
    {
        $this->assertFalse(
            VersionFieldSubField::allowsEditing(SubFieldType::OBJECT, true, false)
        );

        $this->assertFalse(
            VersionFieldSubField::allowsEditing(SubFieldType::OBJECT, false, false)
        );
    }

    public function test_an_array_is_editable_only_while_it_holds_plain_strings(): void
    {
        // Children are what turn an array's items into objects.
        $this->assertTrue(
            VersionFieldSubField::allowsEditing(SubFieldType::ARRAY, false, false)
        );

        $this->assertFalse(
            VersionFieldSubField::allowsEditing(SubFieldType::ARRAY, true, false)
        );

        // An array's enum values constrain each of its items, so it is a choice as well.
        $this->assertFalse(
            VersionFieldSubField::allowsEditing(SubFieldType::ARRAY, false, true)
        );
    }

    public function test_a_boolean_is_editable(): void
    {
        $this->assertTrue(
            VersionFieldSubField::allowsEditing(SubFieldType::BOOLEAN, false, false)
        );
    }

    public function test_the_instance_judges_itself_from_its_own_children(): void
    {
        $subField = new VersionFieldSubField([
            'name' => ['en-GB' => 'Pillars'],
            'sub_code_name' => 'pillars',
            'type' => SubFieldType::ARRAY,
        ]);

        $subField->setRelation('children', collect([new VersionFieldSubField]));

        $this->assertFalse($subField->supportsEditing());

        $subField->setRelation('children', collect());

        $this->assertTrue($subField->supportsEditing());
    }
}
