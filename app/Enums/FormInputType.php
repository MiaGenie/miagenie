<?php

namespace App\Enums;

use App\Concerns\Enum\WithInputOptions;
use App\Concerns\Enum\WithTitle;

enum FormInputType: int
{
    use WithInputOptions;
    use WithTitle;

    case TEXT = 1;
    case NUMBER = 2;
    case RANGE = 3;
    case DATE = 4;
    case DATETIME = 5;
    case EMAIL = 6;
    case URL = 7;

    /**
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::TEXT => 'text',
            self::NUMBER => 'number',
            self::RANGE => 'range',
            self::DATE => 'date',
            self::DATETIME => 'datetime',
            self::EMAIL => 'email',
            self::URL => 'url'
        };
    }

    /**
     * @return bool
     */
    public function hasLength(): bool
    {
        return match ($this) {
            self::TEXT => true,
            self::NUMBER => false,
            self::RANGE => false,
            self::DATE => false,
            self::DATETIME => false,
            self::EMAIL => true,
            self::URL => true
        };
    }

    /**
     * @return bool
     */
    public function hasStep(): bool
    {
        return match ($this) {
            self::TEXT => false,
            self::NUMBER => true,
            self::RANGE => true,
            self::DATE => false,
            self::DATETIME => false,
            self::EMAIL => false,
            self::URL => false
        };
    }

    /**
     * @return bool
     */
    public function hasValues(): bool
    {
        return match ($this) {
            self::TEXT => false,
            self::NUMBER => true,
            self::RANGE => true,
            self::DATE => false,
            self::DATETIME => false,
            self::EMAIL => false,
            self::URL => false
        };
    }

    /**
     * @return bool
     */
    public function isIdentifier(): bool
    {
        return match ($this) {
            self::TEXT => true,
            self::NUMBER => false,
            self::RANGE => false,
            self::DATE => false,
            self::DATETIME => false,
            self::EMAIL => false,
            self::URL => false
        };
    }
}
