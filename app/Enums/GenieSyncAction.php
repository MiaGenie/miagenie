<?php

namespace App\Enums;

use App\Concerns\Enum\WithTitle;

enum GenieSyncAction: int
{
    use WithTitle;

    case CREATE = 1;
    case UPDATE = 2;
    case DELETE = 3;
    case STATUS = 4;
    case LIST = 5;
    case RETRIEVE = 6;

    /**
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::CREATE => 'create',
            self::UPDATE => 'update',
            self::DELETE => 'delete',
            self::STATUS => 'status',
            self::LIST => 'list',
            self::RETRIEVE => 'retrieve'
        };
    }
}
