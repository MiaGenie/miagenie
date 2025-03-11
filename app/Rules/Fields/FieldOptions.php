<?php

namespace App\Rules\Fields;

use App\Enums\FormFieldType;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class FieldOptions implements ValidationRule, DataAwareRule
{
    /**
     * @var Object
     */
    private Object $fieldType;

    /**
     * @var array
     */
    private array $filledOptions;

    /**
     * @var array
     */
    private array $checkedOptions;

    /**
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->fieldType->get('hasOptions')) {
            return;
        }

        if (!self::validEmptyOptions()) {
            $fail('genie.field_options_required')->translate();
            return;
        }

        if (!self::validRadio()) {
            $fail('genie.field_options_invalid_radio')->translate();
            return;
        }

        if (!self::validMulti()) {
            $fail('genie.field_options_invalid_checked')->translate();
        }
    }

    /**
     * @param array $data
     * @return void
     */
    public function setData(array $data): void
    {
        $this->fieldType = FormFieldType::withFieldOptions($data['field_type'], true);
        $this->filledOptions = array_reduce($data['options'], self::filledOptions(...), []);
        $this->checkedOptions = array_reduce($data['options'], self::checkedOptions(...), []);
    }

    /**
     * @param mixed $result
     * @param mixed $option
     * @return mixed
     */
    private function filledOptions(mixed $result, mixed $option): mixed
    {
        $result[$option['group']] ?? $result[$option['group']] = 0;
        $result[$option['group']]++;
        return $result;
    }

    /**
     * @param mixed $result
     * @param mixed $option
     * @return mixed
     */
    private function checkedOptions(mixed $result, mixed $option): mixed
    {
        $result[$option['group']] ?? $result[$option['group']] = 0;
        $result[$option['group']] += $option['checked'];
        return $result;
    }

    /**
     * @return bool
     */
    private function validEmptyOptions(): bool
    {
        return max($this->filledOptions) > 0;
    }

    /**
     * @return bool
     */
    private function validRadio(): bool
    {
        return !(min($this->filledOptions) === 1 && $this->fieldType->get('isRadio'));
    }

    /**
     * @return bool
     */
    private function validMulti(): bool
    {
        return !(max($this->checkedOptions) > 1 && !$this->fieldType->get('hasMulti'));
    }

}
