<?php

namespace App\Genie\Ideas;

use App\Models\Strategy;

/**
 * The content pillars a strategy holds, read the one way every ideas class needs them.
 *
 * A pillar is the object the strategy's `content_pillars` field stores — title, importance,
 * style, description, topics — and its title is what an idea records as its pillar.
 */
final class ContentPillars
{
    public const FIELD = 'content_pillars';

    public const TITLE = '0_title';

    /**
     * Every pillar, as stored.
     *
     * @return list<array<string, mixed>>
     */
    public static function of(?Strategy $strategy): array
    {
        $pillars = $strategy?->content[self::FIELD] ?? [];

        if (! is_array($pillars)) {
            return [];
        }

        return array_values(array_filter($pillars, fn ($pillar) => is_array($pillar)));
    }

    /**
     * @return list<string>
     */
    public static function titles(?Strategy $strategy): array
    {
        $titles = array_map(fn (array $pillar) => self::titleOf($pillar), self::of($strategy));

        return array_values(array_filter($titles, fn (?string $title) => filled($title)));
    }

    /**
     * The pillar with a title, or null when the strategy has none by that name.
     *
     * @return array<string, mixed>|null
     */
    public static function find(?Strategy $strategy, string $title): ?array
    {
        foreach (self::of($strategy) as $pillar) {
            if (self::titleOf($pillar) === $title) {
                return $pillar;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $pillar
     */
    public static function titleOf(array $pillar): ?string
    {
        $title = $pillar[self::TITLE] ?? null;

        return is_string($title) ? $title : null;
    }
}
