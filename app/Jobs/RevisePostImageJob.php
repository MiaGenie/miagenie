<?php

namespace App\Jobs;

use App\Enums\ImageRevisionMode;
use App\Genie\Images\ImageOptions;
use App\Genie\Images\ImageRevision;
use App\Genie\Posts\PostMediaSync;
use App\Genie\Posts\PostsContext;
use App\Genie\Posts\PostsImageContext;
use App\Genie\Posts\PostsRequest;
use App\Genie\Posts\WorkspaceFileToMedia;
use App\Models\AiRun;
use App\Models\AiRunStepFile;
use App\Models\GeniePost;
use App\Models\RuleStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Inovector\Mixpost\Events\Post\PostUpdated;
use Inovector\Mixpost\Facades\WorkspaceManager;
use Inovector\Mixpost\Models\Media;
use Throwable;

/**
 * Redoes one image of a post and puts the result on the Mixpost draft.
 *
 * One try only: an image call is paid for, and a failure is shown on the slot for the user to
 * decide. The workspace is set explicitly because every Mixpost model and the media path read it.
 */
class RevisePostImageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    /**
     * The models are queued bare: a queued model's loaded relations are re-loaded on the worker
     * through the global scopes, and the workspace scope has no current workspace until handle()
     * sets one. Everything needed is read again inside the job.
     *
     * @param  RuleStep  $template  the rule's fix or rethink step, whose message and model profile the call uses
     */
    public function __construct(
        public GeniePost $post,
        public AiRunStepFile $image,
        public RuleStep $template,
        public ImageRevisionMode $mode,
        public ?string $feedback = null,
        public ?string $brief = null,
    ) {
        $this->post = $post->withoutRelations();
        $this->image = $image->withoutRelations();
        $this->template = $template->withoutRelations();

        $this->onQueue(AiRunJob::QUEUE);
    }

    public function handle(ImageRevision $revision, WorkspaceFileToMedia $media, PostMediaSync $sync): void
    {
        WorkspaceManager::setCurrent($this->post->workspace);

        $runStep = $this->image->runStep;
        $run = AiRun::withoutWorkspace()->findOrFail($runStep->run_id);
        $locale = $this->post->workspace->locale ?? app()->getFallbackLocale();
        $position = (int) $this->image->position;

        $context = new PostsImageContext(
            new PostsContext($run, $this->template, PostsRequest::fromRun($run), $locale, $this->post),
            $this->mode === ImageRevisionMode::RETHINK ? $this->brief : $this->image->brief,
            $this->mode === ImageRevisionMode::FIX ? $this->feedback : null,
            $position,
        );

        $revised = $revision->revise($this->image, $run, $this->mode, $context, $locale, new ImageOptions('1:1', 'medium'), $this->template);

        $previous = $this->post->slot($position)['media_id'] ?? null;

        $mediaId = (string) $media->copy($revised->file)->id;

        $sync->replace($this->post, $position, $mediaId);

        $this->post->update([
            'images' => $this->lineageWith($position, $revised->workspace_file_id, $mediaId),
        ]);

        if ($previous !== null && (string) $previous !== $mediaId) {
            $sync->deleteIfUnreferenced(Media::withoutWorkspace()->find($previous));
        }

        PostUpdated::dispatch($this->post->mixpostPost);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Genie post image revision failed', [
            'post_id' => $this->post->id,
            'image_id' => $this->image->id,
            'exception' => $exception?->getMessage(),
        ]);

        // A failure after the file was stored leaves the old slot marked; clear it so the slot is usable again.
        $this->image->update([
            'revising_at' => null,
            'revision_error' => $this->image->fresh()?->revision_error ?? mb_substr((string) $exception?->getMessage(), 0, 255),
        ]);
    }

    /**
     * @return list<array{position: int, file_id: int|null, media_id: string|null}>
     */
    protected function lineageWith(int $position, ?int $fileId, string $mediaId): array
    {
        $lineage = $this->post->images ?? [];
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
