<?php

namespace App\Http\Requests\Workspace\Post;

use App\Enums\ImageRevisionMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Fix details wants feedback; rethink wants the brief to start over from.
 */
class RegeneratePostImage extends FormRequest
{
    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::enum(ImageRevisionMode::class)],
            'feedback' => [Rule::requiredIf(fn () => $this->mode() === ImageRevisionMode::FIX), 'nullable', 'string', 'max:2000'],
            'brief' => [Rule::requiredIf(fn () => $this->mode() === ImageRevisionMode::RETHINK), 'nullable', 'string', 'max:5000'],
        ];
    }

    public function mode(): ?ImageRevisionMode
    {
        return ImageRevisionMode::tryFrom((string) $this->input('mode'));
    }

    public function feedback(): ?string
    {
        $feedback = trim((string) $this->input('feedback'));

        return $feedback === '' ? null : $feedback;
    }

    public function brief(): ?string
    {
        $brief = trim((string) $this->input('brief'));

        return $brief === '' ? null : $brief;
    }
}
