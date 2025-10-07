<?php

namespace App\Enums;

use App\Concerns\Enum\FromName;
use App\Concerns\Enum\HasState;
use App\Concerns\Enum\WithTitle;

enum RunStatus: int
{
    use WithTitle;
    use FromName;
    use HasState;

    case OPEN = 1;
    case RUNNING = 2;
    case ERROR = 3;
    case PENDING_REVIEW = 4;
    case REVIEWED = 5;
    case COMPLETE = 6;



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
            self::COMPLETE => 'complete',
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
            self::PENDING_REVIEW => true,
            default => false,
        };
    }

    /**
     * @return bool
     */
    public function isComplete(): bool
    {
        return match ($this) {
            self::COMPLETE => true,
            default => false,
        };
    }
}
