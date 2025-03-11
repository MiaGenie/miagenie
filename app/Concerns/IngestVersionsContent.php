<?php

namespace App\Concerns;

use App\Enums\FormFieldType;
use App\Enums\FormInputType;
use App\Models\VersionField;
use Illuminate\Support\Collection;
use Inovector\Mixpost\Enums\Genie\GenieVersionFieldType;
use App\Models\Version;

trait IngestVersionsContent

{
    /**
     * @return Collection
     */
    protected function getVersionContent(): Collection
    {
        return $this->fieldList->mapWithKeys(function ($field) {

            return [$field->code_name => $this->input('content.' . $field->code_name)];
        });
    }

    /**
     * @return int
     */
    protected function getVersionId(): int
    {
        return Version::firstOrFailByUuid($this->input('version'))->id;
    }

    /**
     * @return Collection
     */
    protected function getValidationRules(): Collection
    {
        return $this->fieldList->mapWithKeys(function ($field) {

            $fieldType = FormFieldType::withFieldOptions($field->field_type->value, true);

            $rule = $field->required ? 'required' : 'nullable';

            $rule .= $this->getFieldRules($field, $fieldType);
            $rule .= $fieldType->get('isInput') ? $this->getInputRules($field) : '';

            return ['content.' . $field->code_name => $rule];
        });
    }

    /**
     * @param VersionField $field
     * @param Collection $fieldType
     * @return string
     */
    private function getFieldRules(VersionField $field, Collection $fieldType): string
    {
        if ($fieldType->get('name') === 'TEXTAREA') {
            $rule = '|string';
            $rule .= $this->getLengthRule($field, $fieldType->get('name'));
        }

        return $rule ??  '';
    }

    /**
     * @param VersionField $field
     * @return string
     */
    private function getInputRules(VersionField $field): string
    {
        $inputType = FormInputType::withInputOptions($field->input_type?->value, true);
        $rule = '';

        switch ($field->input_type?->name) {
            case 'TEXT':
                $rule .= '|string';
                break;

            case 'NUMBER':
            case 'RANGE':
                $rule .= $field->step ? '|multiple_of:' . $field->step : '|numeric';
                $rule .= $field->min_value ? '|min:' . $field->min_value : '';
                $rule .= $field->max_value ? '|max:' . $field->max_value : '';
                break;

            case 'DATE':
            case 'DATETIME':
                $rule .= '|date';
                break;

            case 'URL':
                $rule .= '|url';
                break;

            case 'EMAIL':
                $rule .= '|email';
                break;
        }

        $rule .= $inputType->get('hasLength') ? $this->getLengthRule($field, $inputType->get('name')) : '';

        return $rule;
    }

    /**
     * @param VersionField $field
     * @param string $type
     * @return string
     */
    private function getLengthRule(VersionField $field, string $type): string
    {
        $rule = $field->min_length ? '|min:' . $field->min_length : '';

        $rule .= '|max:';
        $rule .= min(
            (int) $field->max_length,
            constant('App\Constants\FormTypeDefaults::' . $type . '_MAX_LENGTH')
        );

        return $rule;
    }
}
