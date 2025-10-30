<?php

namespace App\Enums;

use App\Concerns\Enum\FromName;

use App\Concerns\Enum\WithTitle;

enum FunnelStage: int
{
    use WithTitle;
    use FromName;

    case GLOBAL = 1;
    case AWARENESS = 2;
    case PROBLEM_AWARENESS = 3;
    case CONSIDERATION = 4;
    case CONVERSION = 5;
    case REPURCHASE = 6;

    /**
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::GLOBAL => 'global',
            self::AWARENESS => 'awareness',
            self::PROBLEM_AWARENESS => 'problem_awareness',
            self::CONSIDERATION => 'consideration',
            self::CONVERSION => 'conversion',
            self::REPURCHASE => 'repurchase'
        };
    }
}
