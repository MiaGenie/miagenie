<?php

namespace App\Enums;

use App\Concerns\Enum\FromName;
use App\Concerns\Enum\WithTitle;

/**
 * The content format Genie may generate for a social channel.
 */
enum PostFormat: int
{
    use FromName;
    use WithTitle;

    case SIMPLE_POST = 1;
    case CAROUSEL = 2;
    case SHORT_VIDEO = 3;
    case LONG_VIDEO = 4;

    public function title(): string
    {
        return match ($this) {
            self::SIMPLE_POST => 'simple_post',
            self::CAROUSEL => 'carousel',
            self::SHORT_VIDEO => 'short_video',
            self::LONG_VIDEO => 'long_video',
        };
    }
}
