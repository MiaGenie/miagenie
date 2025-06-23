<?php

namespace App\Http\Requests\Admin;

use App\Concerns\Requests\IngestAssistantFields;
use App\Enums\GenieSyncStatus;
use App\Jobs\AssistantJob;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\AssistantType;
use App\Models\Assistant;

class StoreAssistant extends FormRequest
{
    use IngestAssistantFields;

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'assistant_type' => [Rule::enum(AssistantType::class)],
            'instructions' => ['required'],
            'model' => ['required'],
            'response_format' => ['required'],
            'json_schema' => [Rule::requiredIf($this->input('response_format') === 'json_schema')],
        ];
    }

    /**
     * @return Assistant
     */
    public function handle(): Assistant
    {
        $assistant = Assistant::create([
            'name' => $this->input('name'),
            'assistant_type' => $this->input('assistant_type'),
            'description' => $this->input('description'),
            'instructions' => $this->input('instructions'),
            'model' => $this->input('model'),
            'vector_id' => $this->input('vector_id'),
            'response_format' => $this->input('response_format'),
            'json_schema' => $this->input('response_format') === 'json_schema' ? $this->input('json_schema') : '',
            'temperature' => $this->input('temperature'),
            'top_p' => $this->input('top_p'),
            'reasoning_effort' => $this->input('reasoning_effort'),
            'assistant_provider_id' => $this->input('assistant_provider_id'),
            'status' => GenieSyncStatus::CREATING
        ]);

        AssistantJob::dispatch($assistant, 'upload');

        return $assistant;
    }

    /**
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->ingestParameters();
    }
}
