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
    case FILE = 7;
    case JSON_SCHEMA = 8;
    case TEXT = 9;
    case VISUAL = 10;

    public function title(): string
    {
        return match ($this) {
            self::INPUT => 'input',
            self::TEXTAREA => 'textarea',
            self::DROP_DOWN => 'drop_down',
            self::CHECKBOX => 'checkbox',
            self::RADIO => 'radio',
            self::RADIO_GROUP => 'radio_group',
            self::FILE => 'file',
            self::JSON_SCHEMA => 'json_schema',
            self::TEXT => 'text',
            self::VISUAL => 'visual',
        };
    }

    public function hasGroups(): bool
    {
        return match ($this) {
            self::INPUT => false,
            self::TEXTAREA => false,
            self::DROP_DOWN => false,
            self::CHECKBOX => false,
            self::RADIO => false,
            self::RADIO_GROUP => true,
            self::FILE => false,
            self::JSON_SCHEMA => false,
            self::TEXT => false,
            self::VISUAL => false
        };
    }

    public function hasLength(): bool
    {
        return match ($this) {
            self::INPUT => false,
            self::TEXTAREA => true,
            self::DROP_DOWN => false,
            self::CHECKBOX => false,
            self::RADIO => false,
            self::RADIO_GROUP => false,
            self::FILE => false,
            self::JSON_SCHEMA => false,
            self::TEXT => false,
            self::VISUAL => false
        };
    }

    public function hasMulti(): bool
    {
        return match ($this) {
            self::INPUT => false,
            self::TEXTAREA => false,
            self::DROP_DOWN => false,
            self::CHECKBOX => true,
            self::RADIO => false,
            self::RADIO_GROUP => false,
            self::FILE => false,
            self::JSON_SCHEMA => false,
            self::TEXT => false,
            self::VISUAL => false
        };
    }

    public function hasOptions(): bool
    {
        return match ($this) {
            self::INPUT => false,
            self::TEXTAREA => false,
            self::DROP_DOWN => true,
            self::CHECKBOX => true,
            self::RADIO => true,
            self::RADIO_GROUP => true,
            self::FILE => false,
            self::JSON_SCHEMA => false,
            self::TEXT => false,
            self::VISUAL => false
        };
    }

    public function hasRows(): bool
    {
        return match ($this) {
            self::INPUT => false,
            self::TEXTAREA => true,
            self::DROP_DOWN => false,
            self::CHECKBOX => false,
            self::RADIO => false,
            self::RADIO_GROUP => false,
            self::FILE => false,
            self::JSON_SCHEMA => false,
            self::TEXT => false,
            self::VISUAL => false
        };
    }

    public function isInput(): bool
    {
        return match ($this) {
            self::INPUT => true,
            self::TEXTAREA => false,
            self::DROP_DOWN => false,
            self::CHECKBOX => false,
            self::RADIO => false,
            self::RADIO_GROUP => false,
            self::FILE => false,
            self::JSON_SCHEMA => false,
            self::TEXT => false,
            self::VISUAL => false
        };
    }

    public function isOutput(): bool
    {
        return match ($this) {
            self::INPUT => false,
            self::TEXTAREA => false,
            self::DROP_DOWN => false,
            self::CHECKBOX => false,
            self::RADIO => false,
            self::RADIO_GROUP => false,
            self::FILE => false,
            self::JSON_SCHEMA => true,
            self::TEXT => true,
            self::VISUAL => true
        };
    }

    public function isFile(): bool
    {
        return match ($this) {
            self::INPUT => false,
            self::TEXTAREA => false,
            self::DROP_DOWN => false,
            self::CHECKBOX => false,
            self::RADIO => false,
            self::RADIO_GROUP => false,
            self::FILE => true,
            self::JSON_SCHEMA => false,
            self::TEXT => false,
            self::VISUAL => false
        };
    }

    public function isRadio(): bool
    {
        return match ($this) {
            self::INPUT => false,
            self::TEXTAREA => false,
            self::DROP_DOWN => false,
            self::CHECKBOX => false,
            self::RADIO => true,
            self::RADIO_GROUP => true,
            self::FILE => false,
            self::JSON_SCHEMA => false,
            self::TEXT => false,
            self::VISUAL => false
        };
    }
}
