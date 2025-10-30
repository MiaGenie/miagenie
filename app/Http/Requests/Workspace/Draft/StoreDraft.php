<?php

namespace App\Http\Requests\Workspace\Draft;

use App\Enums\DraftStatus;
use App\Models\Draft;
use Illuminate\Validation\Rule as ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDraft extends FormRequest
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
     * @return Draft
     */
    public function handle(): Draft
    {
        return Draft::create([
            'idea_id' => $this->input('idea_id'),
            'goal' => $this->input('goal'),
            'caption' => $this->input('caption'),
            'media' => $this->input('media'),
            'status' => $this->input('status'),
        ]);
    }
}
