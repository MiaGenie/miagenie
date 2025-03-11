<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SnakeCase implements ValidationRule
{
    /**
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $snakePattern = '/^[a-z0-9]+(_[a-z0-9]+)*/';

        $charsPattern = '/[^a-z0-9_]+/';

        if (!preg_match($snakePattern, $value) || preg_match($charsPattern, $value)) {
            $fail('genie.field_name_invalid')->translate();
        }
    }
}
