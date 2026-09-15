<?php

namespace App\Enums;

use App\Concerns\Enum\FromName;
use App\Concerns\Enum\HasState;
use App\Concerns\Enum\WithTitle;

/**
 * Where Genie stands with a post it is producing.
 *
 * Only the generation is tracked here: the draft itself, its approval and its scheduling are
 * Mixpost's (PostStatus). READY means every step of the last run has written its part.
 */
enum GeniePostStatus: int
{
    use FromName;
    use HasState;
    use WithTitle;

    case GENERATING = 1;
    case READY = 2;
    case FAILED = 3;

    public function title(): string
    {
        return match ($this) {
            self::GENERATING => 'generating',
            self::READY => 'ready',
            self::FAILED => 'failed',
        };
    }

    public function isError(): bool
    {
        return $this === self::FAILED;
    }

    public function requiresUpdate(): bool
    {
        return $this === self::GENERATING;
    }

    public function isComplete(): bool
    {
        return $this === self::READY;
    }
}
