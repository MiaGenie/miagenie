<?php

namespace Tests\Unit;

use App\Enums\RuleSubType;
use App\Enums\SubFieldType;
use App\Enums\VersionGroupType;
use App\Genie\Versions\VersionImporter;
use App\Models\RuleStep;
use App\Models\VersionField;
use App\Models\VersionFieldSubField;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function test_a_fields_styling_keeps_only_the_vocabulary(): void
    {
        $importer = new VersionImporter;
        $strategy = ['group_type' => VersionGroupType::STRATEGIES->value];

        $this->assertSame(
            ['block' => 'feature', 'class' => 'strategy-tone-orange'],
            $importer->fieldStyling([...$strategy, 'block' => 'feature', 'class' => 'strategy-tone-orange font-bold']),
        );
        $this->assertSame(['block' => null, 'class' => null], $importer->fieldStyling([...$strategy, 'block' => 'group', 'class' => 'font-bold']));
        $this->assertSame(['block' => null, 'class' => null], $importer->fieldStyling($strategy));
    }

    public function test_a_field_outside_the_strategy_group_is_imported_without_styling(): void
    {
        $importer = new VersionImporter;
        $styled = ['block' => 'feature', 'class' => 'strategy-tone-orange'];

        $this->assertSame(
            ['block' => null, 'class' => null],
            $importer->fieldStyling([...$styled, 'group_type' => VersionGroupType::BRIEFINGS->value]),
        );
        $this->assertSame(['block' => null, 'class' => null], $importer->fieldStyling($styled));
    }

    public function test_a_strategy_fields_icon_is_kept_with_its_path(): void
    {
        $icon = $this->importer()->fieldIcon([
            'group_type' => VersionGroupType::STRATEGIES->value,
            'icon' => 'mdi:rocket-launch',
            'icon_path' => 'M13.13 22.19L11.5 18.36C13.07 17.78 14.54 17 15.9 16.09Z',
        ]);

        $this->assertSame(['icon' => 'mdi:rocket-launch', 'icon_path' => 'M13.13 22.19L11.5 18.36C13.07 17.78 14.54 17 15.9 16.09Z'], $icon);
    }

    public function test_a_field_outside_the_strategy_group_is_imported_without_an_icon(): void
    {
        $icon = $this->importer()->fieldIcon([
            'group_type' => VersionGroupType::BRIEFINGS->value,
            'icon' => 'mdi:rocket-launch',
            'icon_path' => 'M0 0L1 1Z',
        ]);

        $this->assertSame(['icon' => null, 'icon_path' => null], $icon);
    }

    /**
     * The path is bound to a `<path d>`; a hand-edited file must not get markup or other text
     * into it, and an icon without a usable path is dropped whole, as the form would refuse it.
     *
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public static function unusableIcons(): array
    {
        return [
            'markup in the path' => ['mdi:rocket', 'M0 0"/><script>alert(1)</script>'],
            'text in the path' => ['mdi:rocket', 'hello world'],
            'path too long' => ['mdi:rocket', str_pad('M0 0', 20001, ' 0')],
            'no path' => ['mdi:rocket', null],
            'not an mdi name' => ['fa:rocket', 'M0 0Z'],
            'name not a string' => [['mdi:rocket'], 'M0 0Z'],
            'no icon' => [null, 'M0 0Z'],
        ];
    }

    #[DataProvider('unusableIcons')]
    public function test_an_icon_the_form_would_refuse_is_dropped_whole(mixed $name, mixed $path): void
    {
        $icon = $this->importer()->fieldIcon([
            'group_type' => VersionGroupType::STRATEGIES->value,
            'icon' => $name,
            'icon_path' => $path,
        ]);

        $this->assertSame(['icon' => null, 'icon_path' => null], $icon);
    }

    public function test_a_step_whose_sub_type_may_search_keeps_its_web_search(): void
    {
        $this->assertSame([], $this->importer()->stepWebSearch([
            'rule_sub_type' => RuleSubType::BRIEFINGS->value,
            'web_search' => true,
            'web_search_max' => 4,
        ]));
    }

    public function test_a_step_whose_sub_type_may_not_search_is_imported_with_web_search_off(): void
    {
        $off = ['web_search' => false, 'web_search_max' => null, 'web_search_domains' => null];

        $this->assertSame($off, $this->importer()->stepWebSearch([
            'rule_sub_type' => RuleSubType::IDEAS_FORMAT->value,
            'web_search' => true,
            'web_search_max' => 4,
            'web_search_domains' => ['example.org'],
        ]));
        $this->assertSame($off, $this->importer()->stepWebSearch(['web_search' => true]));
    }

    public function test_a_sub_fields_block_is_kept_only_when_it_fits_the_exported_shape(): void
    {
        $importer = new VersionImporter;
        $title = ['type' => SubFieldType::STRING->value, 'children' => []];

        $this->assertSame(
            ['block' => 'numbered_cards', 'class' => 'strategy-emphasis-muted'],
            $importer->subFieldStyling([
                'type' => SubFieldType::ARRAY->value,
                'block' => 'numbered_cards',
                'class' => 'strategy-emphasis-muted',
                'children' => [$title],
            ]),
        );
        $this->assertSame(
            ['block' => null, 'class' => null],
            $importer->subFieldStyling(['type' => SubFieldType::ARRAY->value, 'block' => 'bullets', 'children' => [$title]]),
        );
        $this->assertSame(
            ['block' => null, 'class' => null],
            $importer->subFieldStyling(['type' => SubFieldType::ARRAY->value, 'block' => 'group', 'class' => null, 'children' => []]),
        );
    }
}
