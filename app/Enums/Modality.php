<?php

namespace App\Enums;

use App\Concerns\Enum\FromName;
use App\Concerns\Enum\WithTitle;

/**
 * What a step produces.
 *
 * Every step is text today. Image and video are coming, and they differ in where the result is
 * kept: text lands in the step's `output`, while image and video land in workspace files linked
 * through `genie_ai_run_step_files`.
 */
enum Modality: int
{
    use FromName;
    use WithTitle;

    case TEXT = 1;
    case IMAGE = 2;
    case VIDEO = 3;

    public function title(): string
    {
        return match ($this) {
            self::TEXT => 'text',
            self::IMAGE => 'image',
            self::VIDEO => 'video',
        };
    }

    /**
     * Whether the result is a stored file rather than a value on the step.
     */
    public function isFile(): bool
    {
        return match ($this) {
            self::TEXT => false,
            self::IMAGE, self::VIDEO => true,
        };
    }
}
