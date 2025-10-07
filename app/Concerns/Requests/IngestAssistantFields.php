<?php

namespace App\Concerns\Requests;

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
    protected function ingestParameters(): void
    {
        $fields = $this->filterFields();
        $this->merge($fields);
    }

    /**
     * @return array
     */
    private function filterFields(): array
    {
        $fieldModel = AIModel::where('model', $this->input('model'))->firstOrFail();
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
}
