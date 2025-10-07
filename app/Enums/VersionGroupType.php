<?php

namespace App\Enums;

use App\Concerns\Enum\FromName;
use App\Concerns\Enum\WithGroupOptions;
use App\Concerns\Enum\WithTitle;

enum VersionGroupType: int
{
    use WithTitle;
    use WithGroupOptions;
    use FromName;

    case BRIEFINGS = 1;
    case COMPETITORS = 2;
    case STRATEGIES = 3;

    /**
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::BRIEFINGS => 'briefings',
            self::COMPETITORS => 'competitors',
            self::STRATEGIES => 'strategies'
        };
    }

    /**
     * @return bool
     */
    public function hasIdentifier(): bool
    {
        return match ($this) {
            self::BRIEFINGS => false,
            self::COMPETITORS => true,
            self::STRATEGIES => false
        };
    }
}
