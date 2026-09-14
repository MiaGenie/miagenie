<?php

namespace App\Enums;

/**
 * How a generated image is redone.
 *
 * FIX keeps the composition: the previous file is attached as a reference and the reviewer's
 * feedback says what to change. RETHINK starts over from a brief, with no reference.
 */
enum ImageRevisionMode: string
{
    case FIX = 'fix';
    case RETHINK = 'rethink';

    public function attachesReference(): bool
    {
        return $this === self::FIX;
    }

    public function needsFeedback(): bool
    {
        return $this === self::FIX;
    }

    public function needsBrief(): bool
    {
        return $this === self::RETHINK;
    }
}
