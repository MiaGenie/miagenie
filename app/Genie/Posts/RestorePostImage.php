<?php

namespace App\Genie\Posts;

use App\Models\AiRunStepFile;
use App\Models\GeniePost;
use Illuminate\Support\Facades\DB;
use Inovector\Mixpost\Events\Post\PostUpdated;
use Inovector\Mixpost\Models\Media;

/**
 * Puts a previous version of a slot back into the post.
 *
 * Every version keeps its Genie file and its slot row; only its Mixpost media copy is gone once a
 * later version replaced it. Restoring copies the file into the media library again, swaps it
 * into the slot in place, and flips the rows so the chosen one is current and today's current one
 * is superseded — `genie_ai_run_step_files` is unique on (run step, file), so rows are flipped, not
 * re-inserted. No model call is made, so nothing is logged.
 */
class RestorePostImage
{
    public function __construct(
        protected WorkspaceFileToMedia $media = new WorkspaceFileToMedia,
        protected PostMediaSync $sync = new PostMediaSync,
    ) {}

    /**
     * @throws PostsException when a run or a revision is working on the post
     */
    public function restore(GeniePost $post, AiRunStepFile $version): void
    {
        if ($post->isBusy()) {
            throw PostsException::busy();
        }

        $position = (int) $version->position;

        $current = AiRunStepFile::where('run_step_id', $version->run_step_id)
            ->where('position', $position)
            ->current()
            ->latest('id')
            ->first();

        if ($current?->is($version) || ! $version->file) {
            return;
        }

        $previousMediaId = $post->slot($position)['media_id'] ?? null;

        $mediaId = (string) $this->media->copy($version->file)->id;

        DB::transaction(function () use ($post, $version, $current, $position, $mediaId) {
            $current?->update(['superseded_at' => now(), 'revising_at' => null]);

            $version->update(['superseded_at' => null, 'revising_at' => null, 'revision_error' => null]);

            $this->sync->replace($post, $position, $mediaId);

            $post->update(['images' => $this->lineageWith($post, $position, $version->workspace_file_id, $mediaId)]);
        });

        if ($previousMediaId !== null && (string) $previousMediaId !== $mediaId) {
            $this->sync->deleteIfUnreferenced(Media::withoutWorkspace()->find($previousMediaId));
        }

        PostUpdated::dispatch($post->mixpostPost);
    }

    /**
     * @return list<array{position: int, file_id: int|null, media_id: string|null}>
     */
    protected function lineageWith(GeniePost $post, int $position, ?int $fileId, string $mediaId): array
    {
        $lineage = $post->images ?? [];
        $found = false;

        foreach ($lineage as $index => $slot) {
            if ((int) ($slot['position'] ?? 0) === $position) {
                $lineage[$index] = ['position' => $position, 'file_id' => $fileId, 'media_id' => $mediaId];
                $found = true;
            }
        }

        if (! $found) {
            $lineage[] = ['position' => $position, 'file_id' => $fileId, 'media_id' => $mediaId];
        }

        usort($lineage, fn (array $a, array $b) => (int) $a['position'] <=> (int) $b['position']);

        return array_values($lineage);
    }
}
