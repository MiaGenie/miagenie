<?php

namespace App\Concerns\Requests;

use App\Constants\FormTypeDefaults;
use App\Enums\FormFieldType;
use App\Enums\FormInputType;
use Illuminate\Support\Collection;

trait IngestVersionFields
{
    /**
     * @var Collection
     */
    private Collection $fieldType;

    /**
     * @var Collection
     */
    private Collection $inputType;

    /**
     * @return void
     */
    protected function ingestOptions(): void
    {
        $this->fieldType ??= FormFieldType::withFieldOptions($this->input('field_type'), true);

        $processedOptions = [[]];

        if ($this->fieldType->get('hasOptions')) {
            if ($this->fieldType->get('hasGroups')) {
                $processedOptions = $this->input('options');
            } else {
                $processedOptions = array_slice($this->input('options'), 0, 1);
            }
        }

        $this->merge(array_merge(...$processedOptions));
    }

    /**
     * @return void
     */
    protected function ingestParameters(): void
    {
        $fieldParams = $this->filterFieldParams();
        $inputParams = $this->filterInputParams();

        $this->merge(array_merge($fieldParams, $inputParams));
    }

    /**
     * @return string
     */
    protected function getMaxLength(): string
    {
        if (!$type = $this->getTypeForLength()) {
            return FormTypeDefaults::DEFAULT_MAX_LENGTH;
        }

        return constant('App\Constants\FormTypeDefaults::' . $type . '_MAX_LENGTH');
    }

    /**
     * @return string
     */
    private function getTypeForLength(): string
    {
        $this->fieldType ??= FormFieldType::withFieldOptions($this->input('field_type'), true);

        if ($this->fieldType->get('name') === 'TEXTAREA') {
            return 'TEXTAREA';
        }

        return $this->fieldType->get('name') == 'INPUT' ? FormInputType::tryFrom($this->input('input_type'))->name : '';
    }

    /**
     * @return array
     */
    private function filterFieldParams(): array
    {
        $this->fieldType ??= FormFieldType::withFieldOptions($this->input('field_type'), true);

        $params = [];

        if (!$this->fieldType->get('hasRows')) {
            $params['rows'] = null;
        }

        if (!$this->fieldType->get('hasLength') && !$this->fieldType->get('isInput')) {
            $params['min_length'] = null;
            $params['max_length'] = null;
        }

        return $params;
    }


    /**
     * @return array
     */
    private function filterInputParams(): array
    {
        $this->fieldType ??= FormFieldType::withFieldOptions($this->input('field_type'), true);

        $params = [];

        if (!$this->fieldType->get('isInput')) {
            $params['min_value'] = null;
            $params['max_value'] = null;
            $params['step'] = null;

            return $params;
        }

        $this->inputType ??= FormInputType::withInputOptions($this->input('input_type'), true);

        if (!$this->inputType->get('hasLength')) {
            $params['min_length'] = null;
            $params['max_length'] = null;
        }

        if (!$this->inputType->get('hasValues')) {
            $params['min_value'] = null;
            $params['max_value'] = null;
        }

        if (!$this->inputType->get('hasStep')) {
            $params['step'] = null;
        }

        return $params;
    }
}
