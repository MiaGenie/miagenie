<?php

namespace App\Enums;

use App\Concerns\Enum\FromName;
use App\Concerns\Enum\HasState;
use App\Concerns\Enum\WithTitle;

enum RunResponseStatus: int
{
    use WithTitle;
    use FromName;
    use HasState;

    case COMPLETED = 1;
    case FAILED = 2;
    case IN_PROGRESS = 3;
    case CANCELLED = 4;
    case QUEUED = 5;
    case INCOMPLETE = 6;

    /**
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::COMPLETED => 'completed',
            self::FAILED => 'failed',
            self::IN_PROGRESS => 'in_progress',
            self::CANCELLED => 'cancelled',
            self::QUEUED => 'queued',
            self::INCOMPLETE => 'incomplete',
        };
    }

    /**
     * @return bool
     */
    public function hasError(): bool
    {
        return match ($this) {
            self::FAILED, self::CANCELLED, self::INCOMPLETE => true,
            default => false,
        };
    }

    /**
     * @return bool
     */
    public function requiresUpdate(): bool
    {
        return match ($this) {
            self::IN_PROGRESS, self::QUEUED => true,
            default => false,
        };
    }

    /**
     * @return bool
     */
    public function isComplete(): bool
    {
        return match ($this) {
            self::COMPLETED => true,
            default => false,
        };
    }
}
