<?php

namespace App\Http\Requests\Admin;

use App\Models\RuleStep;
use Arr;
use Illuminate\Foundation\Http\FormRequest;
use Inovector\Mixpost\Util;

class UpdateRuleStepTranslations extends FormRequest
{
    public function rules(): array
    {
        return [
            'instructions' => ['nullable', 'string', 'min:1', 'max:10000'],
            'message' => ['required', 'string', 'max:5000'],
            'review_message_user' => ['nullable', 'string', 'max:5000'],
            'review_message_system' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function handle(): int
    {
        $record = RuleStep::firstOrFailByUuid($this->route('step'));
        $locale = $this->route('locale');

        $locales = Util::config('locales');
        $baseLocale = Arr::first($locales, function ($value) {
            return $value['short'] === $this->defaultLocale;
        });
        $record->setLocale($baseLocale['short']);

        foreach ($record->translatable as $field) {
            if ($record->{$field} !== $this->input($field) && $record->{$field} !== null && $record->{$field} !== '') {
                $record->setTranslation($field, $locale, $this->input($field));
            }
        }

        return $record->save();
    }
}
