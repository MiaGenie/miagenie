<?php

namespace App\Enums;

use App\Concerns\Enum\WithTitle;

enum FileStatus: int
{
    use WithTitle;

    case DISABLED = 1;
    case ENABLED = 2;
    case TESTING = 3;
    case ARCHIVED = 4;

    /**
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::DISABLED => 'disabled',
            self::ENABLED => 'enabled',
            self::TESTING => 'testing',
            self::ARCHIVED => 'archived'
        };
    }
}
