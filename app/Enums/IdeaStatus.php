<?php

namespace App\Enums;

use App\Concerns\Enum\FromName;
use App\Concerns\Enum\HasState;
use App\Concerns\Enum\WithTitle;

enum IdeaStatus: int
{
    use WithTitle;
    use FromName;
    use HasState;

    case PENDING_REVIEW = 1;
    case APPROVED = 2;
    case TRASH = 9;

    /**
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::APPROVED => 'approved',
            self::PENDING_REVIEW => 'pending_review',
            self::TRASH => 'trash',
        };
    }

    /**
     * @return bool
     */
    public function isError(): bool
    {
        return match ($this) {
            self::TRASH => true,
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
            self::APPROVED => true,
            default => false,
        };
    }
}
