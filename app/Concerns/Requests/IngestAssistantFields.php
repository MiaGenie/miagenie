<?php

namespace App\Concerns\Requests;

use App\Constants\FormTypeDefaults;
use App\Enums\FormFieldType;
use App\Enums\FormInputType;
use App\Models\AIModel;
use Illuminate\Support\Collection;

trait IngestAssistantFields
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

        $this->merge(['options' => array_merge(...$processedOptions)]);
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
     * @return array
     */
    private function filterFieldParams(): array
    {
        $allModels = AIModel::all();

        $fieldModel = $allModels->firstOrFail(function ($model) {
            return ($model->model === $this->input('model'));
        });

        $params = [];

        if (!$fieldModel->file_search) {
            $params['vector_id'] = null;
            $params['file_search'] = null;
        }

        if (!$fieldModel->reasoning_effort) {
            $params['reasoning_effort'] = null;
        }

        if (!$fieldModel->json_schema) {
            $params['json_schema'] = null;
        }

        if (!$fieldModel->temperature_top_p) {
            $params['temperature'] = null;
            $params['top_p'] = null;
        }

        return $params;
    }


    /**
     * @return array
     */
    private function filterInputParams(): array
    {
        return [];
    }
}
