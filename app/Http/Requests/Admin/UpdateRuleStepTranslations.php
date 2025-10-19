<?php

namespace App\Http\Requests\Admin;

use App\Models\RuleStep;
use Arr;
use Illuminate\Foundation\Http\FormRequest;
use Inovector\Mixpost\Util;

class UpdateRuleStepTranslations extends FormRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'instructions' => ['required', 'string'],
            'json_schema' => ['nullable', 'json'],
            'message' => ['required', 'string'],
            'review_message_user' => ['nullable', 'string'],
            'review_message_system' => ['nullable', 'string']
        ];
    }

    /**
     * @return int
     */
    public function handle(): int
    {
        $record = RuleStep::firstOrFailByUuid($this->route('step'));
        $locale = $this->route('locale');

        $locales = Util::config('locales');
        $baseLocale = Arr::first($locales, function ($value) {
            return $value['short'] === $this->defaultLocale;
        });
        $record->setLocale($baseLocale);

        foreach ($record->translatable as $field) {
            if ($record->{$field} !== $this->input($field) && $record->{$field} !== null && $record->{$field} !== '') {
                $record->setTranslation($field, $locale, $this->input($field));
            }
        }

        return $record->save();
    }

}
