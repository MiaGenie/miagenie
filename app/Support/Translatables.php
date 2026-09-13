<?php

namespace App\Support;

use App\Models\RuleStep;
use App\Models\VersionField;
use App\Models\VersionFieldOption;
use App\Models\VersionFieldSubField;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Inovector\Mixpost\Util;

/**
 * The models whose translatable attributes the admin can translate inline.
 *
 * The translation endpoints address a record as `{type}/{uuid}/{attribute}`; this map is what
 * turns the type into a model, so adding a translatable model is one more entry here.
 */
final class Translatables
{
    /**
     * @var array<string, class-string<Model>>
     */
    private const TYPES = [
        'field' => VersionField::class,
        'option' => VersionFieldOption::class,
        'sub_field' => VersionFieldSubField::class,
        'step' => RuleStep::class,
    ];

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return array_keys(self::TYPES);
    }

    /**
     * @return class-string<Model>|null
     */
    public static function modelFor(string $type): ?string
    {
        return self::TYPES[$type] ?? null;
    }

    /**
     * The record a type and uuid point at, or a 404 for an unknown type or record.
     */
    public static function resolve(string $type, string $uuid): Model
    {
        $model = self::modelFor($type);

        abort_unless($model !== null, 404);

        return $model::firstOrFailByUuid($uuid);
    }

    /**
     * Whether the model declares the attribute as translatable.
     */
    public static function isTranslatable(Model $model, string $attribute): bool
    {
        return in_array($attribute, (array) ($model->translatable ?? []), true);
    }

    /**
     * @throws ValidationException when the attribute is not translatable on that model
     */
    public static function assertAttribute(Model $model, string $attribute): void
    {
        if (! self::isTranslatable($model, $attribute)) {
            throw ValidationException::withMessages([
                'attribute' => __('genie.translation_attribute_invalid', ['attribute' => $attribute]),
            ]);
        }
    }

    /**
     * Whether the attribute holds a list of texts (translated by position) rather than one text.
     */
    public static function isList(Model $model, string $attribute): bool
    {
        return in_array($attribute, (array) ($model->translatableLists ?? []), true);
    }

    /**
     * The attribute's text in the source locale — a list of strings for a list attribute.
     *
     * @return string|list<string>
     */
    public static function sourceOf(Model $model, string $attribute): string|array
    {
        $source = $model->getTranslation($attribute, self::sourceLocale(), false);

        return self::isList($model, $attribute)
            ? array_values(array_map('strval', (array) ($source ?: [])))
            : (string) $source;
    }

    /**
     * The locale the admin writes content in; every other locale is a translation of it.
     */
    public static function sourceLocale(): string
    {
        return (string) Util::config('default_locale');
    }

    /**
     * The configured locales other than the source, in their long form.
     *
     * @return list<string>
     */
    public static function targetLocales(): array
    {
        return array_values(array_filter(
            array_column((array) Util::config('locales'), 'long'),
            fn (string $locale) => $locale !== self::sourceLocale()
        ));
    }

    /**
     * The attribute's translation for every target locale, empty where there is none: an empty
     * string for a text, an empty list for a list attribute.
     *
     * @return array<string, string|list<string>>
     */
    public static function translationsOf(Model $model, string $attribute): array
    {
        $stored = $model->getTranslations($attribute);
        $isList = self::isList($model, $attribute);

        $translations = [];

        foreach (self::targetLocales() as $locale) {
            $translations[$locale] = $isList
                ? array_values(array_map('strval', (array) ($stored[$locale] ?? [])))
                : (string) ($stored[$locale] ?? '');
        }

        return $translations;
    }
}
