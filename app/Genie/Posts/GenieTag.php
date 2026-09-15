<?php

namespace App\Genie\Posts;

use Inovector\Mixpost\Models\Tag;

/**
 * The tag every draft Genie creates carries, so Mixpost's posts list can filter them.
 *
 * One row per workspace, created the first time it is needed; the workspace comes from the
 * current one, like every other Mixpost model.
 */
final class GenieTag
{
    public const NAME = 'Genie';

    public const HEX_COLOR = '7C3AED';

    public static function resolve(): Tag
    {
        return Tag::query()->where('name', self::NAME)->first()
            ?? Tag::create(['name' => self::NAME, 'hex_color' => self::HEX_COLOR]);
    }
}
