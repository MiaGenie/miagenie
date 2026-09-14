<?php

namespace App\Genie\Posts;

use App\Models\GeniePost;
use Inovector\Mixpost\Models\Media;
use Inovector\Mixpost\Models\PostVersion;

/**
 * The only code that edits the media list of a Genie post's original version.
 *
 * Mixpost's version is the truth for what the post carries and the user edits it freely, so
 * every write here is the smallest possible change: a first generation appends, a regeneration
 * swaps one id in place, a re-add puts one id back. Nothing Genie did not add is ever removed.
 * The list arithmetic is in static helpers so it can be tested without a database.
 */
class PostMediaSync
{
    /**
     * Make sure each given slot's media is in the version, appending the missing ones in slot
     * order after whatever is there.
     *
     * @param  list<array{position: int, media_id: string|null}>  $slots
     */
    public function place(GeniePost $post, array $slots): void
    {
        $this->write($post, fn (array $media) => self::placeIds(
            $media,
            array_values(array_filter(array_map(fn (array $slot) => $slot['media_id'] ?? null, self::sorted($slots)))),
        ));
    }

    /**
     * Put a slot's new media where its previous one sits; when that one is gone, next to the
     * nearest lower slot still in the post; failing that, at the end.
     */
    public function replace(GeniePost $post, int $position, string $mediaId): void
    {
        $old = $post->slot($position)['media_id'] ?? null;

        $this->write($post, fn (array $media) => self::swapIds(
            $media,
            $old === null ? null : (string) $old,
            $mediaId,
            self::anchorsBelow($post, $position),
        ));
    }

    /**
     * Put a slot's current media back into the post at its slot position.
     */
    public function reattach(GeniePost $post, int $position): void
    {
        $mediaId = $post->slot($position)['media_id'] ?? null;

        if ($mediaId === null) {
            return;
        }

        $this->write($post, fn (array $media) => in_array((string) $mediaId, array_map('strval', $media), true)
            ? $media
            : self::swapIds($media, null, (string) $mediaId, self::anchorsBelow($post, $position)));
    }

    /**
     * Delete a media row and its files unless some version in the workspace still shows it.
     */
    public function deleteIfUnreferenced(?Media $media): bool
    {
        if (! $media || PostVersion::hasMedia($media)->exists()) {
            return false;
        }

        $media->deleteFiles();
        $media->delete();

        return true;
    }

    /**
     * Append the ids that are missing, in the given order.
     *
     * @param  list<int|string>  $media
     * @param  list<string>  $ids
     * @return list<string>
     */
    public static function placeIds(array $media, array $ids): array
    {
        $media = array_values(array_map('strval', $media));

        foreach ($ids as $id) {
            if (! in_array((string) $id, $media, true)) {
                $media[] = (string) $id;
            }
        }

        return $media;
    }

    /**
     * Replace `$old` with `$new` at the same index; when `$old` is absent, insert `$new` after
     * the first of `$anchors` (nearest lower slot first) that is present, else append.
     *
     * @param  list<int|string>  $media
     * @param  list<string>  $anchors
     * @return list<string>
     */
    public static function swapIds(array $media, ?string $old, string $new, array $anchors = []): array
    {
        $media = array_values(array_map('strval', $media));

        if ($old !== null && ($index = array_search($old, $media, true)) !== false) {
            $media[$index] = $new;

            return array_values(array_unique($media));
        }

        foreach ($anchors as $anchor) {
            if (($index = array_search((string) $anchor, $media, true)) !== false) {
                array_splice($media, $index + 1, 0, [$new]);

                return array_values(array_unique($media));
            }
        }

        $media[] = $new;

        return array_values(array_unique($media));
    }

    /**
     * The media ids of the slots below a position, nearest first, for placing a slot whose own
     * media is no longer in the post.
     *
     * @return list<string>
     */
    protected static function anchorsBelow(GeniePost $post, int $position): array
    {
        $below = array_filter(
            $post->images ?? [],
            fn (array $slot) => (int) ($slot['position'] ?? 0) < $position && isset($slot['media_id']),
        );

        $below = self::sorted(array_values($below));

        return array_reverse(array_map(fn (array $slot) => (string) $slot['media_id'], $below));
    }

    /**
     * @param  list<array<string, mixed>>  $slots
     * @return list<array<string, mixed>>
     */
    protected static function sorted(array $slots): array
    {
        usort($slots, fn (array $a, array $b) => (int) ($a['position'] ?? 0) <=> (int) ($b['position'] ?? 0));

        return array_values($slots);
    }

    /**
     * Read the original version fresh, rewrite its first content item's media, save.
     *
     * @param  callable(list<int|string>): list<string>  $change
     */
    protected function write(GeniePost $post, callable $change): void
    {
        $version = $post->originalVersion();

        if (! $version) {
            return;
        }

        $content = $version->content ?? [];

        if ($content === []) {
            $content = [['body' => '', 'media' => [], 'url' => null, 'video_thumbs' => []]];
        }

        $content[0]['media'] = $change($content[0]['media'] ?? []);

        $version->content = array_values($content);
        $version->save();
    }
}
