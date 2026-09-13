<?php

namespace Tests\Unit;

use App\Enums\FormFieldType;
use App\Enums\RuleSubType;
use App\Enums\SubFieldType;
use App\Enums\VersionGroupType;
use App\Genie\Versions\VersionExporter;
use App\Models\ModelProfile;
use App\Models\RuleStep;
use App\Models\VersionField;
use App\Models\VersionFieldOption;
use App\Models\VersionFieldSubField;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class VersionExporterTest extends TestCase
{
    protected function exporter(): VersionExporter
    {
        return new VersionExporter;
    }

    protected function field(int $id, string $codeName, VersionGroupType $group = VersionGroupType::STRATEGIES): VersionField
    {
        $field = new VersionField([
            'uuid' => "uuid-field-$id",
            'version_id' => 7,
            'group_type' => $group,
            'name' => ['en-GB' => "Field $id", 'pt-PT' => "Campo $id"],
            'code_name' => $codeName,
            'field_type' => FormFieldType::INPUT,
            'position' => $id,
        ]);

        $field->id = $id;
        $field->created_at = now();
        $field->updated_at = now();

        return $field;
    }

    protected function subField(int $id, ?int $parentId, int $position, string $codeName = ''): VersionFieldSubField
    {
        $subField = new VersionFieldSubField([
            'uuid' => "uuid-sub-$id",
            'field_id' => 1,
            'parent_id' => $parentId,
            'name' => ['en-GB' => "Sub $id"],
            'sub_code_name' => $codeName !== '' ? $codeName : "sub_$id",
            'type' => SubFieldType::STRING,
            'position' => $position,
        ]);

        $subField->id = $id;

        return $subField;
    }

    protected function step(?int $dependsOnField, ?int $dependsOnOption, ?string $profileName = null): RuleStep
    {
        $step = new RuleStep([
            'uuid' => 'uuid-step',
            'rule_id' => 3,
            'rule_sub_type' => RuleSubType::BRIEFINGS,
            'name' => 'Step',
            'instructions' => ['en-GB' => 'Do it', 'pt-PT' => 'Faz'],
            'model_profile_id' => 9,
            'depends_on_field' => $dependsOnField,
            'depends_on_option' => $dependsOnOption,
            'position' => 0,
        ]);

        $step->id = 11;

        $step->setRelation('modelProfile', $profileName === null ? null : new ModelProfile(['name' => $profileName]));

        return $step;
    }

    public function test_sub_fields_are_nested_under_their_parents_in_position_order(): void
    {
        // Given flat and shuffled, so nesting must come from parent_id rather than from order.
        $tree = new Collection([
            $this->subField(5, 2, 0),
            $this->subField(2, 1, 1),
            $this->subField(4, 1, 0),
            $this->subField(1, null, 1),
            $this->subField(3, null, 0),
            $this->subField(6, 5, 0),
        ]);

        $nested = $this->exporter()->exportSubFields($tree);

        $this->assertSame(['sub_3', 'sub_1'], array_column($nested, 'sub_code_name'));
        $this->assertSame([], $nested[0]['children']);
        $this->assertSame(['sub_4', 'sub_2'], array_column($nested[1]['children'], 'sub_code_name'));
        $this->assertSame(['sub_5'], array_column($nested[1]['children'][1]['children'], 'sub_code_name'));
        $this->assertSame(['sub_6'], array_column($nested[1]['children'][1]['children'][0]['children'], 'sub_code_name'));
    }

    public function test_parent_ids_handed_back_as_strings_still_nest(): void
    {
        $child = $this->subField(2, null, 0);
        $child->parent_id = '1';

        $nested = $this->exporter()->exportSubFields(new Collection([$child, $this->subField(1, null, 0)]));

        $this->assertCount(1, $nested);
        $this->assertSame(['sub_2'], array_column($nested[0]['children'], 'sub_code_name'));
    }

    public function test_database_identity_is_stripped_and_every_locale_kept(): void
    {
        $field = $this->field(1, 'brand');
        $field->setRelation('options', new Collection([
            tap(new VersionFieldOption([
                'uuid' => 'uuid-option',
                'field_id' => 1,
                'name' => ['en-GB' => 'Yes', 'pt-PT' => 'Sim'],
                'code_name' => 'yes',
                'position' => 0,
            ]), fn (VersionFieldOption $option) => $option->id = 21),
        ]));
        $field->setRelation('subFields', new Collection([$this->subField(1, null, 0)]));

        $exported = $this->exporter()->exportField($field);

        foreach (['id', 'uuid', 'version_id', 'created_at', 'updated_at'] as $column) {
            $this->assertArrayNotHasKey($column, $exported, "$column should not be exported");
        }

        $this->assertSame(['en-GB' => 'Field 1', 'pt-PT' => 'Campo 1'], $exported['name']);
        $this->assertSame(VersionGroupType::STRATEGIES->value, $exported['group_type']);
        $this->assertSame(FormFieldType::INPUT->value, $exported['field_type']);

        $option = $exported['options'][0];
        $this->assertArrayNotHasKey('id', $option);
        $this->assertArrayNotHasKey('uuid', $option);
        $this->assertArrayNotHasKey('field_id', $option);
        $this->assertSame(['en-GB' => 'Yes', 'pt-PT' => 'Sim'], $option['name']);

        $subField = $exported['sub_fields'][0];
        $this->assertArrayNotHasKey('id', $subField);
        $this->assertArrayNotHasKey('uuid', $subField);
        $this->assertArrayNotHasKey('field_id', $subField);
        $this->assertArrayNotHasKey('parent_id', $subField);
        $this->assertSame(SubFieldType::STRING->value, $subField['type']);
    }

    public function test_step_dependencies_and_profile_are_written_by_name(): void
    {
        $field = $this->field(1, 'brand');
        $subField = $this->subField(4, null, 0, 'tone');

        $exported = $this->exporter()->exportStep(
            $this->step(1, 4, 'Fast'),
            new Collection([1 => $field]),
            new Collection([4 => $subField])
        );

        foreach (['id', 'uuid', 'rule_id', 'model_profile_id', 'depends_on_field', 'depends_on_option'] as $column) {
            $this->assertArrayNotHasKey($column, $exported, "$column should not be exported");
        }

        $this->assertSame('Fast', $exported['model_profile']);
        $this->assertSame([
            'group_type' => VersionGroupType::STRATEGIES->value,
            'code_name' => 'brand',
            'sub_code_name' => 'tone',
        ], $exported['depends_on']);
        $this->assertSame(['en-GB' => 'Do it', 'pt-PT' => 'Faz'], $exported['instructions']);
        $this->assertSame(RuleSubType::BRIEFINGS->value, $exported['rule_sub_type']);
    }

    public function test_a_step_without_dependency_or_profile_exports_nulls(): void
    {
        $exported = $this->exporter()->exportStep($this->step(null, null), new Collection, new Collection);

        $this->assertNull($exported['model_profile']);
        $this->assertNull($exported['depends_on']);
    }

    public function test_a_dependency_on_a_field_alone_has_no_sub_code_name(): void
    {
        $dependsOn = $this->exporter()->dependsOn(
            $this->step(1, null),
            new Collection([1 => $this->field(1, 'brand', VersionGroupType::BRIEFINGS)]),
            new Collection
        );

        $this->assertSame([
            'group_type' => VersionGroupType::BRIEFINGS->value,
            'code_name' => 'brand',
            'sub_code_name' => null,
        ], $dependsOn);
    }

    public function test_a_dependency_on_a_missing_field_is_dropped(): void
    {
        $this->assertNull(
            $this->exporter()->dependsOn($this->step(99, 4), new Collection, new Collection([4 => $this->subField(4, null, 0)]))
        );
    }
}
