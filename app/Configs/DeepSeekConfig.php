<?php

namespace App\Configs;

use App\Abstracts\Config;
use App\Contracts\Config as ConfigContract;

class DeepSeekConfig extends Config implements ConfigContract
{
    public function group(): string
    {
        return 'deepseek';
    }

    public function form(): array
    {
        return [
            'api_key' => '',
        ];
    }

    public function encrypted(): array
    {
        return [
            'api_key' => true,
        ];
    }

    public function rules(): array
    {
        return [
            'api_key' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
