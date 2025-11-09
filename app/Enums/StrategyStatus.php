<?php

namespace App\Enums;

use App\Concerns\Enum\FromName;
use App\Concerns\Enum\HasState;
use App\Concerns\Enum\WithTitle;

enum StrategyStatus: int
{
    use WithTitle;
    use FromName;
    use HasState;

    case OPEN = 1;
    case RUNNING = 2;
    case ERROR = 3;
    case PENDING_REVIEW = 4;
    case REVIEWED = 5;
    case PENDING_APPROVAL = 6;
    case APPROVED = 7;



    /**
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::OPEN => 'open',
            self::RUNNING => 'running',
            self::ERROR => 'error',
            self::PENDING_REVIEW => 'pending_review',
            self::REVIEWED => 'reviewed',
            self::PENDING_APPROVAL => 'pending_approval',
            self::APPROVED => 'approved',
        };
    }

    /**
     * @return bool
     */
    public function isError(): bool
    {
        return match ($this) {
            self::ERROR => true,
            default => false,
        };
    }

    /**
     * @return bool
     */
    public function requiresUpdate(): bool
    {
        return match ($this) {
            self::PENDING_REVIEW, self::PENDING_APPROVAL => true,
            default => false,
        };
    }

    /**
     * @return bool
     */
    public function isComplete(): bool
    {
        return match ($this) {
            self::APPROVED => true,
            default => false,
        };
    }
}
