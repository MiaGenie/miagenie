<?php

namespace App\Concerns\Controller;

use App\Models\VersionField;
use Inovector\Mixpost\Facades\WorkspaceManager;

/**
 * Field content is read by the customer, so it follows the language the workspace works in rather
 * than the language the signed-in user reads the app in. Those are different settings: the app
 * locale is a per-user Mixpost setting, while `mixpost_workspaces.locale` is what the genie writes
 * and what the customer is answering in.
 */
trait HasWorkspaceLocale
{
    /**
     * The language the workspace works in.
     */
    protected function workspaceLocale(): string
    {
        return WorkspaceManager::current()?->locale ?? app()->getFallbackLocale();
    }

    /**
     * Fields serialized with every translation resolved in the workspace locale instead of the
     * app locale `VersionField::toArray()` would otherwise use.
     *
     * A translation the workspace locale is missing falls back to `app.fallback_locale`, so a gap
     * shows the original wording rather than nothing.
     *
     * @param  iterable<int, VersionField>  $fields
     * @return array<int, array<string, mixed>>
     */
    protected function localizedFields(iterable $fields): array
    {
        $locale = $this->workspaceLocale();

        $localized = [];

        foreach ($fields as $field) {
            $attributes = $field->toArray();

            foreach ($field->getTranslatableAttributes() as $attribute) {
                if (array_key_exists($attribute, $attributes)) {
                    $attributes[$attribute] = $field->getTranslation($attribute, $locale);
                }
            }

            if ($field->relationLoaded('options')) {
                $attributes['options'] = $field->options->map(function ($option) use ($locale) {
                    return array_merge($option->toArray(), [
                        'name' => $option->getTranslation('name', $locale),
                    ]);
                })->all();
            }

            $localized[] = $attributes;
        }

        return $localized;
    }
}
