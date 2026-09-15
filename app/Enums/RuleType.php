<?php

namespace App\Enums;

use App\Concerns\Enum\FromName;
use App\Concerns\Enum\WithTitle;

enum RuleType: int
{
    use FromName;
    use WithTitle;

    case STRATEGY = 1;
    case IDEAS = 2;

    /**
     * Turns an approved idea into a Mixpost draft: caption plus one generated image per brief.
     */
    case POSTS = 5;

    public function title(): string
    {
        return match ($this) {
            self::STRATEGY => 'strategy',
            self::IDEAS => 'ideas',
            self::POSTS => 'posts',
        };
    }
}
