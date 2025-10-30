<?php

namespace App\Enums;

use App\Concerns\Enum\FromName;
use App\Concerns\Enum\WithTitle;

enum RuleType: int
{
    use WithTitle;
    use FromName;

    case STRATEGY = 1;
    case IDEAS = 2;
    case DRAFTS = 3;


    /**
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::STRATEGY => 'strategy',
            self::IDEAS => 'ideas',
            self::DRAFTS => 'drafts'
        };
    }
}
