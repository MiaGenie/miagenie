<?php

namespace Tests\Unit;

use App\Enums\SubFieldType;
use App\Genie\Schema\StepSchemaBuilder;
use App\Models\VersionField;
use App\Models\VersionFieldSubField;
use Illuminate\Support\Collection;
use Tests\TestCase;

class StepSchemaBuilderTest extends TestCase
{
    protected int $nextId = 1;

    /**
     * Build an unsaved strategy field with its sub-field tree already attached, so the
     * compiler can be exercised without touching the database.
     *
     * @param  array<int, VersionFieldSubField>  $subFields
     */
    protected function field(string $codeName, array $subFields = []): VersionField
    {
        $field = new VersionField(['code_name' => $codeName]);
        $field->setRelation('subFields', new Collection($subFields));

        return $field;
    }

    /**
     * Build an unsaved sub-field, assigning the id/parent_id wiring the compiler walks.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<int, VersionFieldSubField>  $children
     */
    protected function subField(
        string $codeName,
        SubFieldType $type,
        array $attributes = [],
        array $children = []
    ): VersionFieldSubField {
        $subField = new VersionFieldSubField(array_merge([
            'sub_code_name' => $codeName,
            'name' => ['en-GB' => ucfirst(str_replace('_', ' ', $codeName))],
            'type' => $type,
            'required' => true,
            'position' => 0,
        ], $attributes));

        $subField->id = $this->nextId++;

        foreach ($children as $child) {
            $child->parent_id = $subField->id;
        }

        return $subField;
    }

    public function test_it_keys_the_root_by_field_code_name_and_maps_a_plain_string(): void
    {
        $about = $this->subField('about', SubFieldType::STRING, [
            'description' => ['en-GB' => "The Brand's About section"],
        ]);

        $schema = (new StepSchemaBuilder)->forFields(
            [$this->field('about', [$about])],
            'en-GB'
        );

        $this->assertSame(['about'], array_keys($schema['properties']));
        $this->assertSame('string', $schema['properties']['about']['type']);
        $this->assertSame("The Brand's About section", $schema['properties']['about']['description']);
        $this->assertSame(['about'], $schema['required']);
        $this->assertFalse($schema['additionalProperties']);
    }

    public function test_it_builds_an_array_of_objects_with_item_bounds(): void
    {
        $name = $this->subField('name', SubFieldType::STRING, ['position' => 0]);
        $definition = $this->subField('definition', SubFieldType::STRING, ['position' => 1]);

        $brandValues = $this->subField('brand_values', SubFieldType::ARRAY, [
            'min_items' => 4,
            'max_items' => 6,
        ], [$name, $definition]);

        $schema = (new StepSchemaBuilder)->forFields(
            [$this->field('brand_values', [$brandValues, $name, $definition])],
            'en-GB'
        );

        $property = $schema['properties']['brand_values'];

        $this->assertSame('array', $property['type']);
        $this->assertSame(4, $property['minItems']);
        $this->assertSame(6, $property['maxItems']);
        $this->assertSame('object', $property['items']['type']);
        $this->assertSame(['name', 'definition'], array_keys($property['items']['properties']));

        // Strict mode: nested objects must close and list every property as required.
        $this->assertFalse($property['items']['additionalProperties']);
        $this->assertSame(['name', 'definition'], $property['items']['required']);
    }

    public function test_it_wraps_several_root_sub_fields_in_an_implicit_object(): void
    {
        $strengths = $this->subField('overall_strengths', SubFieldType::STRING, ['position' => 0]);
        $insights = $this->subField('main_insights', SubFieldType::STRING, ['position' => 1]);

        $schema = (new StepSchemaBuilder)->forFields(
            [$this->field('competitor', [$strengths, $insights])],
            'en-GB'
        );

        $property = $schema['properties']['competitor'];

        $this->assertSame('object', $property['type']);
        $this->assertSame(['overall_strengths', 'main_insights'], array_keys($property['properties']));
        $this->assertFalse($property['additionalProperties']);
    }

    public function test_optional_sub_fields_stay_required_but_become_nullable(): void
    {
        $required = $this->subField('main_goal', SubFieldType::STRING, ['position' => 0]);
        $optional = $this->subField('stretch_goal', SubFieldType::STRING, [
            'required' => false,
            'position' => 1,
        ]);

        $schema = (new StepSchemaBuilder)->forFields(
            [$this->field('goals', [$required, $optional])],
            'en-GB'
        );

        $properties = $schema['properties']['goals']['properties'];

        $this->assertSame('string', $properties['main_goal']['type']);
        $this->assertSame(['string', 'null'], $properties['stretch_goal']['type']);
        $this->assertSame(['main_goal', 'stretch_goal'], $schema['properties']['goals']['required']);
    }

    public function test_it_maps_length_and_enum_constraints_and_omits_presentation_columns(): void
    {
        $tone = $this->subField('tone', SubFieldType::STRING, [
            'min_length' => 10,
            'max_length' => 280,
            'enum_values' => ['formal', 'casual'],
            'icon' => 'megaphone',
            'class' => 'col-span-2',
            'block' => 'card',
            'editable' => false,
        ]);

        $schema = (new StepSchemaBuilder)->forFields(
            [$this->field('tone_of_voice', [$tone])],
            'en-GB'
        );

        $property = $schema['properties']['tone_of_voice'];

        $this->assertSame(10, $property['minLength']);
        $this->assertSame(280, $property['maxLength']);
        $this->assertSame(['formal', 'casual'], $property['enum']);
        $this->assertSame('Tone', $property['title']);

        // icon/class/block/editable drive the frontend and must never leak into the contract.
        $this->assertArrayNotHasKey('icon', $property);
        $this->assertArrayNotHasKey('class', $property);
        $this->assertArrayNotHasKey('block', $property);
        $this->assertArrayNotHasKey('editable', $property);
        $this->assertArrayNotHasKey('x-icon', $property);
    }

    public function test_an_array_without_children_is_an_array_of_strings(): void
    {
        $ideas = $this->subField('secondary_ideas', SubFieldType::ARRAY);

        $schema = (new StepSchemaBuilder)->forFields(
            [$this->field('secondary_ideas', [$ideas])],
            'en-GB'
        );

        $property = $schema['properties']['secondary_ideas'];

        $this->assertSame('array', $property['type']);
        $this->assertSame('string', $property['items']['type']);
    }

    public function test_a_field_without_sub_fields_yields_no_schema(): void
    {
        $schema = (new StepSchemaBuilder)->forFields(
            [$this->field('about', [])],
            'en-GB'
        );

        $this->assertNull($schema);
    }

    public function test_it_builds_one_root_property_per_output_field_in_order(): void
    {
        $about = $this->subField('about', SubFieldType::STRING);
        $values = $this->subField('brand_values', SubFieldType::ARRAY);

        $schema = (new StepSchemaBuilder)->forFields([
            $this->field('about', [$about]),
            $this->field('brand_values', [$values]),
        ], 'en-GB');

        $this->assertSame(['about', 'brand_values'], array_keys($schema['properties']));
        $this->assertSame(['about', 'brand_values'], $schema['required']);
    }
}
