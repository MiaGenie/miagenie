<?php

namespace App\Genie\Posts;

use App\Ai\StepAgentFactory;
use App\Enums\Modality;
use App\Genie\Images\ImageContext;
use App\Genie\Images\ImageOptions;
use App\Genie\Images\ImageStep;
use App\Genie\Runs\Runner;
use App\Genie\Runs\RunPrompt;
use App\Genie\Runs\RunState;
use App\Genie\Runs\SchemaDefinition;
use App\Genie\Runs\StepOutput;
use App\Models\AiRunStep;
use App\Models\GeniePost;
use App\Models\WorkspaceFile;
use Inovector\Mixpost\Events\Post\PostUpdated;
use Inovector\Mixpost\Models\Media;

/**
 * Drives a posts run: the shared loop with the idea and the Mixpost post as its context.
 *
 * A text step's caption goes onto the post's original version through PostsOutput; an image
 * step's files are copied into the Mixpost media library and placed on that same version here,
 * following PostMediaSync's rules, with the lineage kept on the Genie post.
 */
class PostsRunner extends Runner
{
    /**
     * @var array<int, GeniePost|null>
     */
    protected array $posts = [];

    public function __construct(
        StepAgentFactory $agents = new StepAgentFactory,
        RunState $state = new PostsRunState,
        StepOutput $output = new PostsOutput,
        PostsStepGate $gate = new PostsStepGate,
        ?ImageStep $images = null,
        protected WorkspaceFileToMedia $media = new WorkspaceFileToMedia,
        protected PostMediaSync $sync = new PostMediaSync,
    ) {
        parent::__construct($agents, $state, $output, $gate, $images);
    }

    protected function prompt(AiRunStep $runStep, string $locale): RunPrompt
    {
        return new PostsPrompt(
            $runStep->run,
            $runStep->step,
            $locale,
            PostsRequest::fromRun($runStep->run),
            $this->post($runStep),
        );
    }

    /**
     * A text step returns the post's caption and title; an image step has no schema at all.
     */
    protected function schema(AiRunStep $runStep, string $locale): ?SchemaDefinition
    {
        $step = $runStep->step;

        if (! $step || ($step->rule_sub_type?->modality() ?? $step->modality) === Modality::IMAGE) {
            return null;
        }

        return PostSchema::forRun($locale);
    }

    protected function label(): string
    {
        return 'posts';
    }

    /**
     * One image per brief the idea carries; a message-only image when it has none.
     *
     * @return list<string|null>
     */
    protected function briefs(AiRunStep $runStep): array
    {
        $briefs = array_values(array_filter(
            $this->post($runStep)?->idea?->images ?? [],
            fn ($brief) => is_string($brief) && filled($brief),
        ));

        return $briefs === [] ? [null] : $briefs;
    }

    /**
     * Square is the one ratio every provider in PostFormats accepts.
     */
    protected function imageOptions(AiRunStep $runStep): ImageOptions
    {
        return new ImageOptions('1:1', 'medium');
    }

    protected function imageContext(AiRunStep $runStep, ?string $brief, int $position): ImageContext
    {
        $run = $runStep->run;
        $locale = $run->workspace->locale ?? app()->getFallbackLocale();

        $posts = new PostsContext($run, $runStep->step, PostsRequest::fromRun($run), $locale, $this->post($runStep));

        return new PostsImageContext($posts, $brief, null, $position);
    }

    /**
     * Copy the step's files into the Mixpost media library and put them on the post.
     *
     * A slot whose media is still there is kept as it is, so a resumed run does not upload the
     * same image twice; a slot without one — first generation, or a media the user deleted from
     * the library — gets a fresh copy.
     *
     * @param  array{images: list<array{position: int, file_id: int|null, file_uuid: string|null}>}  $output
     */
    protected function imagesWritten(AiRunStep $runStep, array $output): void
    {
        $post = $this->post($runStep);

        if (! $post) {
            return;
        }

        $existing = collect($post->images ?? [])->keyBy(fn (array $slot) => (int) ($slot['position'] ?? 0));

        $lineage = [];

        foreach ($output['images'] ?? [] as $image) {
            $position = (int) $image['position'];
            $fileId = $image['file_id'];
            $slot = $existing->get($position);

            $mediaId = isset($slot['media_id']) ? (string) $slot['media_id'] : null;

            $reusable = $mediaId !== null
                && ($slot['file_id'] ?? null) === $fileId
                && Media::withoutWorkspace()->whereKey($mediaId)->exists();

            if (! $reusable && $fileId && ($file = WorkspaceFile::find($fileId))) {
                $mediaId = (string) $this->media->copy($file)->id;
            }

            $lineage[] = ['position' => $position, 'file_id' => $fileId, 'media_id' => $mediaId];
        }

        $post->update(['images' => $lineage, 'run_id' => $runStep->run_id]);

        $this->sync->place($post, $lineage);

        PostUpdated::dispatch($post->mixpostPost);
    }

    protected function post(AiRunStep $runStep): ?GeniePost
    {
        $id = PostsRequest::fromRun($runStep->run)->post;

        if (! array_key_exists($id, $this->posts)) {
            $this->posts[$id] = $id > 0 ? GeniePost::withoutWorkspace()->find($id) : null;
        }

        return $this->posts[$id];
    }
}
