<?php

namespace App\Enums;

use App\Concerns\Enum\WithTitle;

enum RuleType: int
{
    use WithTitle;

    case STRATEGY = 1;
    case CHANNELS = 2;
    case IDEAS = 3;
    case CONTENT = 4;
    case SCHEDULE = 5;

    /**
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::STRATEGY => 'strategy',
            self::CHANNELS => 'channels',
            self::IDEAS => 'ideas',
            self::CONTENT => 'content',
            self::SCHEDULE => 'schedule'
        };
    }
}
