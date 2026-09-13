<?php

namespace Tests\Unit;

use App\Enums\SubFieldType;
use App\Enums\VersionGroupType;
use App\Genie\Versions\VersionImporter;
use App\Models\RuleStep;
use App\Models\VersionField;
use App\Models\VersionFieldSubField;
use Tests\TestCase;

class VersionImporterTest extends TestCase
{
    protected function field(int $id, string $codeName, VersionGroupType $group = VersionGroupType::STRATEGIES): VersionField
    {
        $field = new VersionField([
            'group_type' => $group,
            'code_name' => $codeName,
        ]);

        $field->id = $id;

        return $field;
    }

    protected function subField(int $id, string $codeName): VersionFieldSubField
    {
        $subField = new VersionFieldSubField([
            'sub_code_name' => $codeName,
            'type' => SubFieldType::STRING,
        ]);

        $subField->id = $id;

        return $subField;
    }

    /**
     * An importer already holding the fields a payload's steps may depend on.
     */
    protected function importer(): VersionImporter
    {
        $importer = new VersionImporter;

        $brand = $this->field(10, 'brand');
        $importer->rememberField($brand);
        $importer->rememberSubField($brand, $this->subField(20, 'tone'));

        $importer->rememberField($this->field(11, 'brand', VersionGroupType::BRIEFINGS));

        return $importer;
    }

    public function test_dependency_resolves_to_the_field_and_sub_field_written_in_this_import(): void
    {
        $resolved = $this->importer()->resolveDependency([
            'group_type' => VersionGroupType::STRATEGIES->value,
            'code_name' => 'brand',
            'sub_code_name' => 'tone',
        ]);

        $this->assertSame([10, 20], $resolved);
    }

    public function test_the_same_code_name_in_another_group_is_a_different_field(): void
    {
        $resolved = $this->importer()->resolveDependency([
            'group_type' => VersionGroupType::BRIEFINGS->value,
            'code_name' => 'brand',
            'sub_code_name' => null,
        ]);

        $this->assertSame([11, null], $resolved);
    }

    public function test_group_type_handed_back_as_a_string_still_matches(): void
    {
        $resolved = $this->importer()->resolveDependency([
            'group_type' => (string) VersionGroupType::STRATEGIES->value,
            'code_name' => 'brand',
        ]);

        $this->assertSame([10, null], $resolved);
    }

    public function test_a_missing_field_drops_the_whole_dependency(): void
    {
        $resolved = $this->importer()->resolveDependency([
            'group_type' => VersionGroupType::STRATEGIES->value,
            'code_name' => 'unknown',
            'sub_code_name' => 'tone',
        ]);

        $this->assertSame([null, null], $resolved);
    }

    public function test_a_missing_sub_field_keeps_the_field_but_drops_the_option(): void
    {
        $resolved = $this->importer()->resolveDependency([
            'group_type' => VersionGroupType::STRATEGIES->value,
            'code_name' => 'brand',
            'sub_code_name' => 'unknown',
        ]);

        $this->assertSame([10, null], $resolved);
    }

    public function test_no_dependency_resolves_to_nulls(): void
    {
        $this->assertSame([null, null], $this->importer()->resolveDependency(null));
        $this->assertSame([null, null], $this->importer()->resolveDependency([]));
        $this->assertSame([null, null], $this->importer()->resolveDependency(['group_type' => 3, 'code_name' => null]));
    }

    public function test_nested_collections_and_identity_are_stripped_from_a_row(): void
    {
        $attributes = (new VersionImporter)->attributesFor(new VersionField, [
            'id' => 5,
            'uuid' => 'abc',
            'created_at' => '2026-01-01',
            'code_name' => 'brand',
            'name' => ['en-GB' => 'Brand'],
            'options' => [['code_name' => 'x']],
            'sub_fields' => [],
        ], ['options', 'sub_fields']);

        $this->assertSame(['code_name' => 'brand', 'name' => ['en-GB' => 'Brand']], $attributes);
    }

    public function test_a_null_translation_is_left_out_so_it_stays_null(): void
    {
        $attributes = (new VersionImporter)->attributesFor(new RuleStep, [
            'name' => 'Step',
            'description' => null,
            'instructions' => ['en-GB' => 'Do it'],
            'message' => null,
        ]);

        $this->assertSame(['name' => 'Step', 'description' => null, 'instructions' => ['en-GB' => 'Do it']], $attributes);
    }
}
