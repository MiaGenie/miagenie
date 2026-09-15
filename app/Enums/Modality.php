<?php

namespace App\Enums;

use App\Concerns\Enum\FromName;
use App\Concerns\Enum\WithTitle;
use Illuminate\Support\Collection;

/**
 * What a step produces.
 *
 * A text step is a turn in the run's conversation and lands in the step's `output`; an image step
 * is one or more stateless image calls whose files are kept as workspace files linked through
 * `genie_ai_run_step_files` (see App\Genie\Images). Video is reserved and cannot be configured yet.
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
     * The modalities an admin can give a rule step, as withTitle() rows.
     */
    public static function configurable(): Collection
    {
        return self::withTitle()->reject(fn ($row) => $row['value'] === self::VIDEO->value)->values();
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
