<?php

namespace App\Http\Requests\Admin;

use App\Models\Rule;
use App\Models\RuleStep;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRuleStep extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'assistant_id' => ['required', 'integer'],
            'message' => ['required', 'string'],
            'output' => ['required', 'string']
        ];
    }

    public function handle(): int
    {
        $record = RuleStep::firstOrFailByUuid($this->route('step'));

        return $record->update([
            'name' => $this->input('name'),
            'description' => $this->input('description'),
            'assistant_id' => $this->input('assistant_id'),
            'message' => $this->input('message'),
            'output' => $this->input('output')
        ]);
    }

}
