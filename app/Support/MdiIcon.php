<?php

namespace App\Support;

/**
 * What a stored Material Design Icon may hold: its name (`mdi:rocket-launch`) and the SVG path drawn
 * for it. The path is bound to a `<path d>` on the workspace pages, so it is held to the characters
 * of SVG path data and must never carry markup. The field forms validate against these patterns
 * (ValidatesIcon) and the version import sanitises with them, so both store the same thing.
 */
final class MdiIcon
{
    public const NAME_PATTERN = '/^mdi:[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public const PATH_PATTERN = '/^[MmLlHhVvCcSsQqTtAaZz0-9.,\s+-]+$/';

    public const MAX_NAME_LENGTH = 255;

    /**
     * Comfortably above the longest MDI path (about 4,600 characters).
     */
    public const MAX_PATH_LENGTH = 20000;

    /**
     * The icon and its path when both are what a field may store, otherwise neither.
     *
     * @return array{icon: string|null, icon_path: string|null}
     */
    public static function sanitize(mixed $icon, mixed $path): array
    {
        if (! self::isName($icon) || ! self::isPath($path)) {
            return ['icon' => null, 'icon_path' => null];
        }

        return ['icon' => $icon, 'icon_path' => $path];
    }

    public static function isName(mixed $icon): bool
    {
        return is_string($icon)
            && mb_strlen($icon) <= self::MAX_NAME_LENGTH
            && preg_match(self::NAME_PATTERN, $icon) === 1;
    }

    public static function isPath(mixed $path): bool
    {
        return is_string($path)
            && mb_strlen($path) <= self::MAX_PATH_LENGTH
            && preg_match(self::PATH_PATTERN, $path) === 1;
    }
}
