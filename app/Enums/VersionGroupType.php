<?php

namespace App\Enums;

use App\Concerns\Enum\FromName;
use App\Concerns\Enum\WithGroupOptions;
use App\Concerns\Enum\WithTitle;

enum VersionGroupType: int
{
    use FromName;
    use WithGroupOptions;
    use WithTitle;

    case BRIEFINGS = 1;
    case STRATEGIES = 3;

    public function title(): string
    {
        return match ($this) {
            self::BRIEFINGS => 'briefings',
            self::STRATEGIES => 'strategies'
        };
    }
}
