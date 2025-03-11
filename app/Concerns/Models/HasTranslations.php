<?php

namespace App\Concerns\Models;

use Spatie\Translatable\HasTranslations as BaseHasTranslations;

trait HasTranslations
{
    use BaseHasTranslations;

    /**
     * @return array
     */
    public function toArray(): array
    {
        $attributes = $this->attributesToArray();

        $translatables = array_filter($this->getTranslatableAttributes(), function ($key) use ($attributes) {
            return array_key_exists($key, $attributes);
        });

        foreach ($translatables as $field) {
            $attributes[$field] = $this->getTranslation($field, \App::getLocale());
        }

        return array_merge($attributes, $this->relationsToArray());
    }
}
