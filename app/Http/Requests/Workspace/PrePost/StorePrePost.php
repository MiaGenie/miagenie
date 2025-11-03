<?php

namespace App\Http\Requests\Workspace\PrePost;

use App\Enums\PrePostStatus;
use App\Models\PrePost;
use Illuminate\Validation\Rule as ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePrePost extends FormRequest
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
     * @return PrePost
     */
    public function handle(): PrePost
    {
        return PrePost::create([
            'draft_id' => $this->input('draft_id'),
            'post_id' => $this->input('post_id'),
            'caption' => $this->input('caption'),
            'status' => $this->input('status'),
        ]);
    }
}
