<?php

namespace App\Enums;

use App\Concerns\Enum\FromName;
use App\Concerns\Enum\HasState;
use App\Concerns\Enum\WithTitle;

enum StrategyStatus: int
{
    use FromName;
    use HasState;
    use WithTitle;

    case OPEN = 1;
    case RUNNING = 2;
    case ERROR = 3;
    case PENDING_REVIEW = 4;
    case REVIEWED = 5;
    case PENDING_APPROVAL = 6;
    case APPROVED = 7;

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

    public function isError(): bool
    {
        return match ($this) {
            self::ERROR => true,
            default => false,
        };
    }

    public function requiresUpdate(): bool
    {
        return match ($this) {
            self::PENDING_REVIEW, self::PENDING_APPROVAL => true,
            default => false,
        };
    }

    /**
     * The statuses a strategy holds while its run has not settled. OPEN is among them because a
     * run is created OPEN and only moves to RUNNING once its job starts; a strategy that is OPEN
     * with no run at all is waiting to be generated instead (Strategy::awaitsGeneration()).
     */
    public function isGenerating(): bool
    {
        return match ($this) {
            self::OPEN, self::RUNNING, self::PENDING_REVIEW, self::REVIEWED => true,
            default => false,
        };
    }

    public function isComplete(): bool
    {
        return match ($this) {
            self::APPROVED => true,
            default => false,
        };
    }
}
