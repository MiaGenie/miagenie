<?php

namespace App\Enums;

use App\Concerns\Enum\WithTitle;

enum RuleSubType: int
{
    use WithTitle;

    case COMPETITORS = 1;
    case STRATEGY = 2;

    /**
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::COMPETITORS => 'competitors',
            self::STRATEGY => 'strategy',
        };
    }
}
