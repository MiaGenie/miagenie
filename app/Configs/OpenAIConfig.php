<?php

namespace App\Configs;

use App\Abstracts\Config;

class OpenAIConfig extends Config
{
    /**
     * @return string
     */
    public function group(): string
    {
        return 'openai';
    }

    /**
     * @return array
     */
    public function form(): array
    {
        return [
            'api_key' => '',
            'request_timeout' => ''
        ];
    }

    /**
     * @return array
     */
    public function encrypted(): array
    {
        return [
            'api_key' => true,
            'request_timeout' => false
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            "api_key" => ['required'],
            'request_timeout' => ['nullable', 'integer', 'min:1', 'max:60'],
        ];
    }

    /**
     * @return array
     */
    public function messages(): array
    {
        return [
            'api_key' => __('validation.required', ['attribute' => 'API Key']),
        ];
    }
}
