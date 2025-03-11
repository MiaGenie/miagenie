<?php

namespace App\Enums;

use App\Concerns\Enum\WithFieldOptions;
use App\Concerns\Enum\WithTitle;

enum FormFieldType: int
{
    use WithFieldOptions;
    use WithTitle;

    case INPUT = 1;
    case TEXTAREA = 2;
    case DROP_DOWN = 3;
    case CHECKBOX = 4;
    case RADIO = 5;
    case RADIO_GROUP = 6;

    /**
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::INPUT => 'input',
            self::TEXTAREA => 'textarea',
            self::DROP_DOWN => 'drop_down',
            self::CHECKBOX => 'checkbox',
            self::RADIO => 'radio',
            self::RADIO_GROUP => 'radio_group'
        };
    }

    /**
     * @return bool
     */
    public function hasGroups(): bool
    {
        return match ($this) {
            self::INPUT => false,
            self::TEXTAREA => false,
            self::DROP_DOWN => false,
            self::CHECKBOX => false,
            self::RADIO => false,
            self::RADIO_GROUP => true
        };
    }

    /**
     * @return bool
     */
    public function hasLength(): bool
    {
        return match ($this) {
            self::INPUT => false,
            self::TEXTAREA => true,
            self::DROP_DOWN => false,
            self::CHECKBOX => false,
            self::RADIO => false,
            self::RADIO_GROUP => false
        };
    }

    /**
     * @return bool
     */
    public function hasMulti(): bool
    {
        return match ($this) {
            self::INPUT => false,
            self::TEXTAREA => false,
            self::DROP_DOWN => false,
            self::CHECKBOX => true,
            self::RADIO => false,
            self::RADIO_GROUP => false
        };
    }

    /**
     * @return bool
     */
    public function hasOptions(): bool
    {
        return match ($this) {
            self::INPUT => false,
            self::TEXTAREA => false,
            self::DROP_DOWN => true,
            self::CHECKBOX => true,
            self::RADIO => true,
            self::RADIO_GROUP => true
        };
    }

    /**
     * @return bool
     */
    public function hasRows(): bool
    {
        return match ($this) {
            self::INPUT => false,
            self::TEXTAREA => true,
            self::DROP_DOWN => false,
            self::CHECKBOX => false,
            self::RADIO => false,
            self::RADIO_GROUP => false
        };
    }

    /**
     * @return bool
     */
    public function isInput(): bool
    {
        return match ($this) {
            self::INPUT => true,
            self::TEXTAREA => false,
            self::DROP_DOWN => false,
            self::CHECKBOX => false,
            self::RADIO => false,
            self::RADIO_GROUP => false
        };
    }

    /**
     * @return bool
     */
    public function isRadio(): bool
    {
        return match ($this) {
            self::INPUT => false,
            self::TEXTAREA => false,
            self::DROP_DOWN => false,
            self::CHECKBOX => false,
            self::RADIO => true,
            self::RADIO_GROUP => true
        };
    }
}
