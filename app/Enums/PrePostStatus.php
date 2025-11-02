<?php

namespace App\Enums;

use App\Concerns\Enum\FromName;
use App\Concerns\Enum\WithTitle;

enum PrePostStatus: int
{
    use WithTitle;
    use FromName;

    case CREATED = 1;
    case PUBLISHING = 2;
    case PUBLISHED = 3;

    /**
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::CREATED => 'created',
            self::PUBLISHING => 'publishing',
            self::PUBLISHED => 'published'
        };
    }
}
