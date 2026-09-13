<?php

namespace App\Enums;

use App\Concerns\Enum\FromName;
use App\Concerns\Enum\WithTitle;

enum FunnelStage: int
{
    use FromName;
    use WithTitle;

    case GLOBAL = 1;
    case AWARENESS = 2;
    case PROBLEM_AWARENESS = 3;
    case CONSIDERATION = 4;
    case CONVERSION = 5;
    case REPURCHASE = 6;

    public function title(): string
    {
        return match ($this) {
            self::GLOBAL => 'global',
            self::AWARENESS => 'awareness',
            self::PROBLEM_AWARENESS => 'problem_awareness',
            self::CONSIDERATION => 'consideration',
            self::CONVERSION => 'conversion',
            self::REPURCHASE => 'repurchase'
        };
    }

    /**
     * The case a title() stands for, or null for a title no case has.
     *
     * A structured response names a stage by its title, and a model can still return something
     * the schema's enum did not list, so an unknown title is answered with null rather than an error.
     */
    public static function fromTitle(string $title): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->title() === $title) {
                return $case;
            }
        }

        return null;
    }

    /**
     * GLOBAL is "any stage": a request for it leaves the choice to the model.
     */
    public function isAny(): bool
    {
        return $this === self::GLOBAL;
    }

    /**
     * The stages an idea can actually be in, GLOBAL excluded.
     *
     * @return list<self>
     */
    public static function concrete(): array
    {
        return array_values(array_filter(self::cases(), fn (self $stage) => ! $stage->isAny()));
    }
}
