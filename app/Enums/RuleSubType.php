<?php

namespace App\Enums;

use App\Concerns\Enum\FromName;
use App\Concerns\Enum\WithTitle;

enum RuleSubType: int
{
    use FromName;
    use WithTitle;

    case BRIEFINGS = 12;
    case BRIEFINGS_MULTIPLE = 13;
    case CHANNELS = 14;
    case IDEAS_INITIAL = 21;
    case IDEAS_SIMPLE = 22;
    case IDEAS_MULTIPLE = 23;
    case DRAFTS_INITIAL = 31;
    case DRAFTS = 32;
    case PRE_POSTS_INITIAL = 41;
    case PRE_POSTS = 42;

    public function title(): string
    {
        return match ($this) {
            self::BRIEFINGS => 'briefings',
            self::BRIEFINGS_MULTIPLE => 'briefings_multiple',
            self::CHANNELS => 'channels',
            self::IDEAS_INITIAL => 'ideas_initial',
            self::IDEAS_SIMPLE => 'ideas_simple',
            self::IDEAS_MULTIPLE => 'ideas_multiple',
            self::DRAFTS_INITIAL => 'drafts_initial',
            self::DRAFTS => 'drafts',
            self::PRE_POSTS_INITIAL => 'pre_posts_initial',
            self::PRE_POSTS => 'pre_posts',
        };
    }
}
