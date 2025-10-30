<?php

namespace App\Enums;

use App\Concerns\Enum\FromName;
use App\Concerns\Enum\HasState;
use App\Concerns\Enum\WithTitle;

enum IdeaSource: int
{
    use WithTitle;
    use FromName;

    case GENIE = 1;
    case USER = 2;

    /**
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::GENIE => 'genie',
            self::USER => 'user',
        };
    }
}
