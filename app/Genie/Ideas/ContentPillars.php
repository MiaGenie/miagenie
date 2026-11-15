<?php

namespace App\Genie\Ideas;

use App\Models\Strategy;

/**
 * The content pillars a strategy holds, read the one way every ideas class needs them.
 *
 * A pillar is the object the strategy's `content_pillars` field stores — name, importance,
 * style, description, topics — and its name is what an idea records as its pillar. The key
 * holding the name mirrors the content_pillars sub-field's `sub_code_name`, which the admin can
 * rename: strategies keep whatever key was current when they were generated, so every key a
 * stored strategy may use is tried, the current one first.
 */
final class ContentPillars
{
    public const FIELD = 'content_pillars';

    /**
     * The keys a pillar's name may be stored under: `name` (current) and `title` (strategies
     * generated before the sub-field was renamed).
     *
     * @var list<string>
     */
    public const TITLE_KEYS = ['name', 'title'];

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
     * The pillar's name, from the first title key that holds a non-empty string.
     *
     * @param  array<string, mixed>  $pillar
     */
    public static function titleOf(array $pillar): ?string
    {
        foreach (self::TITLE_KEYS as $key) {
            $title = $pillar[$key] ?? null;

            if (is_string($title) && filled($title)) {
                return $title;
            }
        }

        return null;
    }
}
