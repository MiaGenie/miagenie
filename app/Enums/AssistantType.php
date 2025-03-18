<?php

namespace App\Enums;

use App\Concerns\Enum\WithTitle;

enum AssistantType: int
{
    use WithTitle;

    case STRATEGY = 1;
    case IDEAS = 2;
    case CONTENT = 3;
    case SCHEDULE = 4;

    /**
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::STRATEGY => 'strategy',
            self::IDEAS => 'ideas',
            self::CONTENT => 'content',
            self::SCHEDULE => 'schedule'
        };
    }
}
