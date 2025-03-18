<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\AssistantType;
use App\Models\Assistant;

class StoreAssistant extends FormRequest
{
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

    public function handle(): Assistant
    {

        return Assistant::create([
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
        ]);
    }
}
