<?php

namespace App\Enums;

use App\Concerns\Enum\FromName;
use App\Concerns\Enum\WithTitle;

enum ThreadRunErrors: int
{
    use WithTitle;
    use FromName;


    case SERVER_ERROR = 1;
    case RATE_LIMIT_EXCEEDED = 2;
    case INVALID_PROMPT = 3;

    /**
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::SERVER_ERROR => 'server_error',
            self::RATE_LIMIT_EXCEEDED => 'rate_limit_exceeded',
            self::INVALID_PROMPT => 'invalid_prompt',
        };
    }
}
