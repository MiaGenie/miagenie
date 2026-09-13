<?php

namespace App\Http\Requests\Admin;

use App\Ai\Translation\Translator;
use App\Support\Translatables;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TranslateAttribute extends FormRequest
{
    public function rules(): array
    {
        return [
            'to' => ['required', 'array', 'min:1'],
            'to.*' => ['required', 'string', Rule::in(Translatables::targetLocales())],
        ];
    }

    public function record(): Model
    {
        return Translatables::resolve($this->route('type'), $this->route('record'));
    }

    /**
     * Translate the saved source text into the requested locales and store the results.
     *
     * The source is always what is saved, never what a form holds: the editor only offers
     * translation while its form is clean, so the two are the same by construction.
     *
     * @return array<string, string> the attribute's translations after the write
     *
     * @throws ValidationException when the source text is empty
     */
    public function handle(Translator $translator): array
    {
        $record = $this->record();
        $attribute = $this->route('attribute');

        Translatables::assertAttribute($record, $attribute);

        $sourceLocale = Translatables::sourceLocale();
        $source = Translatables::sourceOf($record, $attribute);

        if (blank(is_array($source) ? array_filter($source, 'filled') : trim($source))) {
            throw ValidationException::withMessages([
                'source' => __('genie.translation_source_empty'),
            ]);
        }

        $translations = is_array($source)
            ? $translator->translateMany($source, $sourceLocale, $this->input('to'))
            : $translator->translate($source, $sourceLocale, $this->input('to'));

        foreach ($translations as $locale => $text) {
            $record->setTranslation($attribute, $locale, $text);
        }

        $record->save();

        return Translatables::translationsOf($record, $attribute);
    }
}
