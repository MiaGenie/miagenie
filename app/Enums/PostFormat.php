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

    /**
     * How many images a post of this format carries, as [min, max] — or null for a format that is
     * a video rather than a set of images.
     *
     * A carousel is capped at ten by the channels that publish it (Instagram, LinkedIn, TikTok);
     * two is the least that reads as a sequence.
     *
     * @return array{int, int}|null
     */
    public function imageRange(): ?array
    {
        return match ($this) {
            self::SIMPLE_POST => [1, 1],
            self::CAROUSEL => [2, 10],
            self::SHORT_VIDEO, self::LONG_VIDEO => null,
        };
    }

    /**
     * The ranges of the formats that carry images, keyed by the enum value the frontend holds.
     *
     * @return array<int, array{int, int}>
     */
    public static function imageRanges(): array
    {
        $ranges = [];

        foreach (self::cases() as $format) {
            if ($range = $format->imageRange()) {
                $ranges[$format->value] = $range;
            }
        }

        return $ranges;
    }
}
