<?php

namespace Tests\Unit;

use App\Models\RuleStep;
use App\Models\VersionField;
use App\Models\VersionFieldOption;
use App\Models\VersionFieldSubField;
use App\Support\Translatables;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class TranslatablesTest extends TestCase
{
    public function test_every_type_maps_to_a_translatable_model(): void
    {
        $this->assertSame(['field', 'option', 'sub_field', 'step'], Translatables::types());

        $this->assertSame(VersionField::class, Translatables::modelFor('field'));
        $this->assertSame(VersionFieldOption::class, Translatables::modelFor('option'));
        $this->assertSame(VersionFieldSubField::class, Translatables::modelFor('sub_field'));
        $this->assertSame(RuleStep::class, Translatables::modelFor('step'));
        $this->assertNull(Translatables::modelFor('workspace'));
    }

    public function test_an_unknown_type_is_not_found(): void
    {
        $this->expectException(NotFoundHttpException::class);

        Translatables::resolve('workspace', 'any-uuid');
    }

    public function test_only_declared_attributes_are_translatable(): void
    {
        $field = new VersionField;

        $this->assertTrue(Translatables::isTranslatable($field, 'name'));
        $this->assertFalse(Translatables::isTranslatable($field, 'code_name'));

        $this->expectException(ValidationException::class);

        Translatables::assertAttribute($field, 'code_name');
    }

    public function test_the_source_locale_is_the_configured_default_and_targets_exclude_it(): void
    {
        $this->assertSame('en-GB', Translatables::sourceLocale());

        $targets = Translatables::targetLocales();

        $this->assertNotContains('en-GB', $targets);
        $this->assertContains('pt-PT', $targets);
    }

    public function test_a_list_attribute_is_read_and_reported_as_lists(): void
    {
        $subField = new VersionFieldSubField([
            'enum_values' => ['en-GB' => ['formal', 'casual'], 'pt-PT' => ['formal', 'informal']],
        ]);

        $this->assertTrue(Translatables::isList($subField, 'enum_values'));
        $this->assertFalse(Translatables::isList($subField, 'name'));

        $this->assertSame(['formal', 'casual'], Translatables::sourceOf($subField, 'enum_values'));

        $translations = Translatables::translationsOf($subField, 'enum_values');

        $this->assertSame(['formal', 'informal'], $translations['pt-PT']);
        $this->assertSame([], $translations['es-ES']);
    }

    public function test_a_cleared_list_attribute_is_an_empty_list(): void
    {
        // Spatie reads a cleared translation back as '' — never let that leak out as [''].
        $this->assertSame([], Translatables::sourceOf(new VersionFieldSubField(['enum_values' => ['en-GB' => null]]), 'enum_values'));
        $this->assertSame([], Translatables::sourceOf(new VersionFieldSubField, 'enum_values'));
    }

    public function test_a_text_attribute_is_read_as_a_string(): void
    {
        $field = new VersionField(['name' => ['en-GB' => 'Name']]);

        $this->assertSame('Name', Translatables::sourceOf($field, 'name'));
        $this->assertSame('', Translatables::sourceOf(new VersionField, 'name'));
    }

    public function test_translations_cover_every_target_locale(): void
    {
        $field = new VersionField(['name' => ['en-GB' => 'Name', 'pt-PT' => 'Nome']]);

        $translations = Translatables::translationsOf($field, 'name');

        $this->assertSame(Translatables::targetLocales(), array_keys($translations));
        $this->assertSame('Nome', $translations['pt-PT']);
        $this->assertSame('', $translations['es-ES']);
        $this->assertArrayNotHasKey('en-GB', $translations);
    }
}
