<?php

namespace App\Enums;

use Illuminate\Support\Collection;

/**
 * The look a strategy field or sub-field may ask for on the strategy pages, stored in its `class`
 * column as space separated tokens.
 *
 * Every token is a real CSS class defined in `resources/css/genie.css`, so the page binds the
 * stored value as it is. A free-text class would not do: Tailwind only builds the classes it finds
 * in the sources, so one typed into the admin would silently not render.
 *
 * A tone only reassigns the `--strategy-accent*` variables, so it recolours everything below the
 * node that carries it.
 */
enum StrategyStyle: string
{
    case TONE_PRIMARY = 'strategy-tone-primary';
    case TONE_MIAGENIE = 'strategy-tone-miagenie';
    case TONE_CYAN = 'strategy-tone-cyan';
    case TONE_ORANGE = 'strategy-tone-orange';
    case EMPHASIS_HIGHLIGHT = 'strategy-emphasis-highlight';
    case EMPHASIS_MUTED = 'strategy-emphasis-muted';
    case ANIMATE_FADE_IN = 'strategy-animate-fade-in';
    case ANIMATE_SLIDE_UP = 'strategy-animate-slide-up';

    public function category(): StrategyStyleCategory
    {
        return match ($this) {
            self::TONE_PRIMARY,
            self::TONE_MIAGENIE,
            self::TONE_CYAN,
            self::TONE_ORANGE => StrategyStyleCategory::TONE,
            self::EMPHASIS_HIGHLIGHT,
            self::EMPHASIS_MUTED => StrategyStyleCategory::EMPHASIS,
            self::ANIMATE_FADE_IN,
            self::ANIMATE_SLIDE_UP => StrategyStyleCategory::ANIMATION,
        };
    }

    public function title(): string
    {
        return __('genie.strategy_style_'.str_replace('-', '_', substr($this->value, strlen('strategy-'))));
    }

    /**
     * The tokens of a stored class value, in the order they were written.
     *
     * @return array<int, string>
     */
    public static function tokens(?string $class): array
    {
        return array_values(array_filter(preg_split('/\s+/', trim((string) $class)) ?: [], 'filled'));
    }

    /**
     * The value to store or render: only the known tokens, the first of each category, or null
     * when none is left.
     *
     * A value from anywhere but the admin form — an imported version, a row edited by hand — is
     * passed through here, so the page is never handed a class the vocabulary does not define.
     */
    public static function sanitize(?string $class): ?string
    {
        $kept = [];

        foreach (self::tokens($class) as $token) {
            $style = self::tryFrom($token);

            if ($style !== null && ! isset($kept[$style->category()->value])) {
                $kept[$style->category()->value] = $style->value;
            }
        }

        return $kept === [] ? null : implode(' ', $kept);
    }

    /**
     * The tokens grouped by category, for the admin pickers.
     *
     * @return Collection<int, array{category: string, title: string, options: array<int, array{value: string, title: string}>}>
     */
    public static function forEditor(): Collection
    {
        return collect(StrategyStyleCategory::cases())
            ->map(fn (StrategyStyleCategory $category) => [
                'category' => $category->value,
                'title' => $category->title(),
                'options' => collect(self::cases())
                    ->filter(fn (self $style) => $style->category() === $category)
                    ->map(fn (self $style) => ['value' => $style->value, 'title' => $style->title()])
                    ->values()
                    ->all(),
            ]);
    }
}
