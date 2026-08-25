<?php

namespace App\Configs;

use App\Abstracts\Config;
use App\Contracts\Config as ConfigContract;

class OpenAIConfig extends Config implements ConfigContract
{
    public function group(): string
    {
        return 'openai';
    }

    public function form(): array
    {
        return [
            'api_key' => '',
            'request_timeout' => '',
        ];
    }

    public function encrypted(): array
    {
        return [
            'api_key' => true,
            'request_timeout' => false,
        ];
    }

    public function rules(): array
    {
        return [
            'api_key' => ['required'],
            'request_timeout' => ['nullable', 'integer', 'min:1', 'max:3600'],
        ];
    }

    public function messages(): array
    {
        return [
            'api_key' => __('validation.required', ['attribute' => 'API Key']),
        ];
    }
}
