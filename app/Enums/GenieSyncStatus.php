<?php

namespace App\Enums;

use App\Concerns\Enum\HasState;
use App\Concerns\Enum\WithTitle;

enum GenieSyncStatus: int
{
    use WithTitle;
    use HasState;

    case CREATING = 1;
    case CREATED = 2;
    case FAILED_CREATION = 3;
    case UPDATING = 4;
    case UPDATED = 5;
    case FAILED_UPDATE = 6;
    case DELETING = 7;
    case DELETED = 8;
    case FAILED_DELETION = 9;

    /**
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::CREATING => 'creating',
            self::CREATED => 'created',
            self::FAILED_CREATION => 'failed-creation',
            self::UPDATING => 'updating',
            self::UPDATED => 'updated',
            self::FAILED_UPDATE => 'failed-update',
            self::DELETING => 'deleting',
            self::DELETED => 'deleted',
            self::FAILED_DELETION => 'failed-deletion'
        };
    }

    /**
     * @return bool
     */
    public function hasError(): bool
    {
        return match ($this) {
            self::FAILED_CREATION, self::FAILED_UPDATE, self::FAILED_DELETION => true,
            default => false,
        };
    }

    /**
     * @return bool
     */
    public function requiresUpdate(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isComplete(): bool
    {
        return match ($this) {
            self::CREATED, self::UPDATED, self::DELETED => true,
            default => false,
        };
    }
}
