<?php

namespace App\Http\Requests\Admin;

use App\Concerns\Requests\IngestVersionFields;
use App\Models\Version;
use App\Models\VersionFieldOption;
use Arr;
use Illuminate\Foundation\Http\FormRequest;
use Inovector\Mixpost\Util;

class UpdateVersionFieldOptionTranslations extends FormRequest
{
    use IngestVersionFields;

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            '*.name' => ['required', 'string', 'max:500']
        ];
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function handle(): void
    {
        $input = collect($this->input())->keyBy('id');
        $locale = $this->route('locale');

        $locales = Util::config('locales');
        $baseLocale = Arr::first($locales, function ($value) {
            return $value['short'] === $this->defaultLocale;
        });

        $ids = $input->pluck('id');
        $records = VersionFieldOption::whereIn('uuid', $ids)->get()->keyBy('uuid');

        $records->each(function (VersionFieldOption $record, $key) use ($input, $locale, $baseLocale) {
            $record->setLocale($baseLocale['long']);
            foreach ($record->translatable as $field) {
                if ($record->{$field} !== $input->get($key)[$field] && $record->{$field} !== null && $record->{$field} !== '') {
                    $record->setTranslation($field, $locale, $input->get($key)[$field]);
                }
            }
            $record->save();
        });
    }
}
