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
        $ids = Arr::pluck($this->input(), 'id');
        $locale = $this->route('locale');

        $locales = Util::config('locales');
        $baseLocale = Arr::first($locales, function ($value) {
            return $value['short'] === $this->defaultLocale;
        });

        $records = VersionFieldOption::whereIn('uuid', $ids)->get();

        $records->each(function (VersionFieldOption $record) use ($locale, $baseLocale) {
            $record->setLocale($baseLocale);
            if ($record->{$field} !== $this->input($field) && $record->{$field} !== null && $record->{$field} !== '') {
                foreach ($record->translatable as $field) {
                    $record->setTranslation($field, $locale, $this->input($field));
                }
                $record->save();
            }
        });
    }
}
