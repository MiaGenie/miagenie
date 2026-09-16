<?php

namespace App\Http\Requests\Admin;

use App\Concerns\Requests\IngestVersionFields;
use App\Models\Version;
use App\Models\VersionField;
use Arr;
use Illuminate\Foundation\Http\FormRequest;
use Inovector\Mixpost\Util;

class UpdateVersionFieldTranslations extends FormRequest
{
    use IngestVersionFields;

    public function rules(): array
    {
        $this->version ??= Version::firstOrFailByUuid($this->route('version'));

        return [
            'name' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'sub_description' => ['nullable', 'string'],
        ];
    }

    /**
     * @throws \Throwable
     */
    public function handle(): int
    {

        $record = VersionField::firstOrFailByUuid($this->route('field'));
        $locale = $this->route('locale');

        $locales = Util::config('locales');
        $baseLocale = Arr::first($locales, function ($value) {
            return $value['short'] === $this->defaultLocale;
        });
        $record->setLocale($baseLocale['long']);

        foreach ($record->translatable as $field) {
            if ($record->{$field} !== $this->input($field) && $record->{$field} !== null && $record->{$field} !== '') {
                $record->setTranslation($field, $locale, $this->input($field));
            }
        }

        return $record->save();
    }
}
