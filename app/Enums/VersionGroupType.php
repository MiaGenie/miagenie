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

    /**
     * Whether a field of this group may carry an icon. Only the strategy fields do: the icon heads
     * the field's section where the workspace reviews and reads its strategy.
     */
    public function supportsIcon(): bool
    {
        return $this === self::STRATEGIES;
    }
}
