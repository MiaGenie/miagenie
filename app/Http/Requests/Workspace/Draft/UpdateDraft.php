<?php

namespace App\Http\Requests\Workspace\Draft;

use App\Enums\DraftStatus;
use App\Models\Draft;
use Illuminate\Validation\Rule as ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDraft extends FormRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'topic' => ['required', 'string', 'max:255'],
            'goal' => ['required', 'string', 'max:5000'],
            'key_ideas' => ['required', 'string', 'max:5000'],
            'media' => ['required', 'string'],
            'status' => [ValidationRule::enum(DraftStatus::class)],
        ];
    }

    /**
     * @return int
     */
    public function handle(): int
    {
        $record = Draft::firstOrFailByUuid($this->route('draft'));

        return $record->update([
            'topic' => $this->input('topic'),
            'goal' => $this->input('goal'),
            'key_ideas' => $this->input('key_ideas'),
            'media' => $this->input('media'),
            'status' => $this->input('status'),
        ]);
    }
}
