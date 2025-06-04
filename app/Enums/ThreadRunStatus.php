<?php

namespace App\Enums;

use App\Concerns\Enum\FromName;
use App\Concerns\Enum\WithTitle;

enum ThreadRunStatus: int
{
    use WithTitle;
    use FromName;


    case QUEUED = 1;
    case IN_PROGRESS = 2;
    case REQUIRES_ACTION = 3;
    case CANCELLING = 4;
    case CANCELLED = 5;
    case FAILED = 6;
    case COMPLETED = 7;
    case INCOMPLETE = 8;
    case EXPIRED = 9;


    /**
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::QUEUED => 'queued',
            self::IN_PROGRESS => 'in_progress',
            self::REQUIRES_ACTION => 'requires_action',
            self::CANCELLING => 'cancelling',
            self::CANCELLED => 'cancelled',
            self::FAILED => 'failed',
            self::COMPLETED => 'completed',
            self::INCOMPLETE => 'incomplete',
            self::EXPIRED => 'expired',
        };
    }
}
