<?php

namespace App\Concerns;

use App\Enums\FormFieldType;
use App\Enums\FormInputType;
use App\Models\VersionField;
use App\Models\WorkspaceVersion;
use Illuminate\Support\Collection;

trait IngestVersionsContent
{
    protected function getVersionContent(): Collection
    {
        return $this->fieldList->mapWithKeys(function ($field) {

            return [$field->code_name => $this->input('content.'.$field->code_name)];
        });
    }

    protected function getVersionId(): int
    {
        return WorkspaceVersion::whereHas('workspace', function ($query) {
            $query->where('uuid', $this->route('workspace'));
        })->firstOrFail()->version_id;

    }

    /**
     * A draft is a form still being filled in, so a required field is allowed to be empty; every
     * other rule still applies, and what is there has to be right.
     */
    protected function getValidationRules(bool $draft = false): Collection
    {
        return $this->fieldList->mapWithKeys(function ($field) use ($draft) {

            $fieldType = FormFieldType::withFieldOptions($field->field_type->value, true);

            $rule = $field->required && ! $draft ? 'required' : 'nullable';

            $rule .= $this->getFieldRules($field, $fieldType);
            $rule .= $fieldType->get('isInput') ? $this->getInputRules($field) : '';

            return ['content.'.$field->code_name => $rule];
        });
    }

    private function getFieldRules(VersionField $field, Collection $fieldType): string
    {
        if ($fieldType->get('name') === 'TEXTAREA') {
            $rule = '|string';
            $rule .= $this->getLengthRule($field, $fieldType->get('name'));
        }

        return $rule ?? '';
    }

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
                $rule .= $field->step ? '|multiple_of:'.$field->step : '|numeric';
                $rule .= $field->min_value ? '|min:'.$field->min_value : '';
                $rule .= $field->max_value ? '|max:'.$field->max_value : '';
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

    private function getLengthRule(VersionField $field, string $type): string
    {
        $rule = $field->min_length ? '|min:'.$field->min_length : '';

        $inputTypeMaxLength = constant('App\Constants\FormTypeDefaults::'.$type.'_MAX_LENGTH');

        $rule .= '|max:';
        $rule .= (int) $field->max_length > 0 ? min((int) $field->max_length, $inputTypeMaxLength) : $inputTypeMaxLength;

        return $rule;
    }
}
