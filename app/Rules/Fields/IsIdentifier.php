<?php

namespace App\Rules\Fields;

use App\Enums\FormFieldType;
use App\Enums\FormInputType;
use App\Enums\VersionGroupType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsIdentifier implements ValidationRule
{

    /**
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value) {
            return;
        }

        $groupType = VersionGroupType::withGroupOptions(request()->input('group_type'), true);
        $fieldType = FormFieldType::withFieldOptions(request()->input('field_type'), true);
        $inputType = FormInputType::withInputOptions(request()->input('input_type'), true);

        if (
            !$groupType->get('hasIdentifier') ||
            !$fieldType->get('isInput') ||
            !$inputType->get('isIdentifier')
        ) {
            $fail('genie.field_is_identifier_invalid')->translate();
        }
    }
}
