<?php

namespace App\Enums;

use App\Concerns\Enum\FromName;
use App\Concerns\Enum\WithTitle;

/**
 * Whether the customer has finished the briefing, rather than whether its answers happen to be
 * filled in: the wizard writes a draft on every question, so the stored content says nothing about
 * the decision to finish.
 */
enum BriefingStatus: int
{
    use FromName;
    use WithTitle;

    case DRAFT = 1;
    case COMPLETE = 2;

    public function title(): string
    {
        return match ($this) {
            self::DRAFT => 'draft',
            self::COMPLETE => 'complete',
        };
    }

    public function isComplete(): bool
    {
        return match ($this) {
            self::COMPLETE => true,
            default => false,
        };
    }
}
