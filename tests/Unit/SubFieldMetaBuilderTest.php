<?php

namespace Tests\Unit;

use App\Enums\SubFieldType;
use App\Genie\Schema\SubFieldMetaBuilder;
use App\Models\VersionField;
use App\Models\VersionFieldSubField;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SubFieldMetaBuilderTest extends TestCase
{
    protected int $nextId = 1;

    /**
     * Build an unsaved strategy field with its whole tree attached, the way the compiler receives
     * it, so nothing here needs the database.
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
            'editable' => true,
            'position' => 0,
        ], $attributes));

        $subField->id = $this->nextId++;

        foreach ($children as $child) {
            $child->parent_id = $subField->id;
        }

        return $subField;
    }

    public function test_a_single_root_is_the_fields_own_node(): void
    {
        $about = $this->subField('about', SubFieldType::STRING);

        $meta = (new SubFieldMetaBuilder)->forFields([$this->field('about', [$about])]);

        $this->assertSame(['about' => ['editable' => true]], $meta);
    }

    public function test_several_roots_are_wrapped_in_an_object(): void
    {
        $title = $this->subField('title', SubFieldType::STRING);
        $body = $this->subField('body', SubFieldType::STRING, ['editable' => false, 'position' => 1]);

        $meta = (new SubFieldMetaBuilder)->forFields([$this->field('post', [$title, $body])]);

        $this->assertFalse($meta['post']['editable']);
        $this->assertTrue($meta['post']['properties']['title']['editable']);
        $this->assertFalse($meta['post']['properties']['body']['editable']);
    }

    public function test_an_objects_children_are_keyed_under_properties(): void
    {
        $summary = $this->subField('summary', SubFieldType::STRING);
        $locked = $this->subField('archetype', SubFieldType::STRING, ['editable' => false, 'position' => 1]);
        $tone = $this->subField('tone_of_voice', SubFieldType::OBJECT, [], [$summary, $locked]);

        $meta = (new SubFieldMetaBuilder)->forFields([
            $this->field('tone_of_voice', [$tone, $summary, $locked]),
        ]);

        // An object is a shape, so it is never editable itself, whatever the column says.
        $this->assertFalse($meta['tone_of_voice']['editable']);
        $this->assertTrue($meta['tone_of_voice']['properties']['summary']['editable']);
        $this->assertFalse($meta['tone_of_voice']['properties']['archetype']['editable']);
    }

    public function test_an_array_of_objects_is_keyed_under_items(): void
    {
        $title = $this->subField('0_title', SubFieldType::STRING);
        $importance = $this->subField('1_importance', SubFieldType::STRING, ['editable' => false, 'position' => 1]);
        $pillars = $this->subField('content_pillars', SubFieldType::ARRAY, [], [$title, $importance]);

        $meta = (new SubFieldMetaBuilder)->forFields([
            $this->field('content_pillars', [$pillars, $title, $importance]),
        ]);

        // Children turn the items into objects, which is what makes the array a structure.
        $this->assertFalse($meta['content_pillars']['editable']);
        $this->assertFalse($meta['content_pillars']['items']['editable']);
        $this->assertTrue($meta['content_pillars']['items']['properties']['0_title']['editable']);
        $this->assertFalse($meta['content_pillars']['items']['properties']['1_importance']['editable']);
    }

    public function test_an_array_of_strings_is_a_leaf(): void
    {
        $dos = $this->subField('3_dos', SubFieldType::ARRAY);

        $meta = (new SubFieldMetaBuilder)->forFields([$this->field('dos', [$dos])]);

        $this->assertSame(['dos' => ['editable' => true]], $meta);
    }

    public function test_a_stored_flag_the_shape_forbids_is_not_trusted(): void
    {
        // An enum is a choice the model made from a fixed list, so freehand editing is refused
        // even though the column still reads true.
        $style = $this->subField('style', SubFieldType::STRING, ['enum_values' => ['bold', 'plain']]);

        $meta = (new SubFieldMetaBuilder)->forFields([$this->field('style', [$style])]);

        $this->assertFalse($meta['style']['editable']);
    }

    public function test_a_field_with_no_sub_fields_is_left_out(): void
    {
        $this->assertSame([], (new SubFieldMetaBuilder)->forFields([$this->field('orphan')]));
    }
}
