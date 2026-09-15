<?php

namespace App\Http\Requests\Workspace\Post;

use Illuminate\Foundation\Http\FormRequest;

class RegeneratePostText extends FormRequest
{
    public function rules(): array
    {
        return [
            'feedback' => ['required', 'string', 'max:2000'],
        ];
    }

    public function feedback(): string
    {
        return trim((string) $this->input('feedback'));
    }
}
