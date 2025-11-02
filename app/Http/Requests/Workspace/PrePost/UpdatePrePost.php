<?php

namespace App\Http\Requests\Workspace\PrePost;

use App\Enums\PrePostStatus;
use App\Models\Draft;
use App\Models\PrePost;
use Illuminate\Validation\Rule as ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePrePost extends FormRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'caption' => ['required', 'string', 'max:3000'],
            'status' => [ValidationRule::enum(PrePostStatus::class)],
        ];
    }

    /**
     * @return int
     */
    public function handle(): int
    {
        $record = PrePost::firstOrFailByUuid($this->route('pre_post'));

        return $record->update([
            'draft_id' => $this->input('draft_id'),
            'caption' => $this->input('caption'),
            'status' => $this->input('status'),
        ]);
    }
}
