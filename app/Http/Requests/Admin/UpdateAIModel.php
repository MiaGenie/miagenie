<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\AIModel;

class UpdateAIModel extends FormRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'model' => ['required', 'string', 'max:255']
        ];
    }

    /**
     * @return int
     */
    public function handle(): int
    {
        $record = AIModel::firstOrFailByUuid($this->route('ai_model'));

        return $record->update([
            'model' => $this->input('model'),
            'json_schema' => $this->input('json_schema', 0),
            'temperature_top_p' => $this->input('temperature_top_p', 0),
            'file_search' => $this->input('file_search', 0),
            'reasoning_effort' => $this->input('reasoning_effort', 0),
        ]);
    }
}
