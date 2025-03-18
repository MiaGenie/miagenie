<?php

namespace App\Contracts;

interface Config
{
    /**
     * @return string
     */
    public function group(): string;

    /**
     * @return array
     */
    public function form(): array;

    /**
     * @return array
     */
    public function encrypted(): array;

    /**
     * @return array
     */
    public function rules(): array;

    /**
     * @return array
     */
    public function messages(): array;
}
