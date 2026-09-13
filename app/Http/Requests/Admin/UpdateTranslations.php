<?php

namespace App\Http\Requests\Admin;

use App\Support\Translatables;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTranslations extends FormRequest
{
    public function rules(): array
    {
        return [
            'translations' => ['required', 'array'],
            'translations.*' => ['nullable', function (string $attribute, mixed $value, \Closure $fail) {
                if (is_string($value)) {
                    if (mb_strlen($value) > 10000) {
                        $fail(__('validation.max.string', ['attribute' => $attribute, 'max' => 10000]));
                    }

                    return;
                }

                if (! is_array($value) || ! array_is_list($value)) {
                    $fail(__('validation.string', ['attribute' => $attribute]));
                }
            }],
            'translations.*.*' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Only configured, non-source locales may be written; the source is edited on the form.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $unknown = array_diff(array_keys((array) $this->input('translations', [])), Translatables::targetLocales());

            if ($unknown !== []) {
                $validator->errors()->add('translations', __('genie.translation_locale_invalid', ['locale' => implode(', ', $unknown)]));
            }
        });
    }

    public function record(): Model
    {
        return Translatables::resolve($this->route('type'), $this->route('record'));
    }

    /**
     * @return array<string, string|list<string>> the attribute's translations after the write
     */
    public function handle(): array
    {
        $record = $this->record();
        $attribute = $this->route('attribute');

        Translatables::assertAttribute($record, $attribute);

        $isList = Translatables::isList($record, $attribute);
        $source = $isList ? Translatables::sourceOf($record, $attribute) : null;

        foreach ((array) $this->input('translations') as $locale => $value) {
            $value = $isList ? $this->alignedList((array) $value, $source) : trim((string) $value);

            if (blank($value)) {
                $record->forgetTranslation($attribute, $locale);
            } else {
                $record->setTranslation($attribute, $locale, $value);
            }
        }

        $record->save();

        return Translatables::translationsOf($record, $attribute);
    }

    /**
     * A translated list matched to the source by position: a blank entry keeps the source
     * value, so the schema's enum never shrinks; a list with nothing translated is dropped.
     *
     * @param  array<int, mixed>  $value
     * @param  list<string>  $source
     * @return list<string>
     */
    protected function alignedList(array $value, array $source): array
    {
        $value = array_values($value);

        $translated = array_filter(
            array_map(fn (mixed $item) => trim((string) $item), $value),
            fn (string $item) => $item !== ''
        );

        if ($translated === []) {
            return [];
        }

        return array_map(
            fn (int $index, string $original) => trim((string) ($value[$index] ?? '')) ?: $original,
            array_keys($source),
            $source
        );
    }
}
