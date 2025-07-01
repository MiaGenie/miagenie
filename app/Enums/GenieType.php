<?php

namespace App\Enums;

use App\Concerns\Enum\FromName;
use App\Concerns\Enum\WithTitle;

enum GenieType: int
{
    use FromName;
    use WithTitle;

    case FILE = 1;
    case VECTOR = 2;
    case ASSISTANT = 3;
    case RUN = 4;
    case RUN_RESPONSE = 5;

    /**
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::FILE => 'file',
            self::VECTOR => 'vector',
            self::ASSISTANT => 'assistant',
            self::RUN => 'run',
            self::RUN_RESPONSE => 'run-response',
        };
    }
}
