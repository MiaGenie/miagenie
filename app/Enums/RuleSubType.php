<?php

namespace App\Enums;

use App\Concerns\Enum\FromName;
use App\Concerns\Enum\WithTitle;

enum RuleSubType: int
{
    use WithTitle;
    use FromName;

    case COMPETITORS = 11;
    case STRATEGY = 12;

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
