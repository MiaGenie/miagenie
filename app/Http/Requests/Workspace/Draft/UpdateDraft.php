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
            'goal' => ['required', 'string', 'max:255'],
            'caption' => ['required', 'string'],
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
            'goal' => $this->input('goal'),
            'caption' => $this->input('caption'),
            'media' => $this->input('media'),
            'status' => $this->input('status'),
        ]);
    }
}
