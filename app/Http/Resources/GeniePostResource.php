<?php

namespace App\Http\Resources;

use App\Models\AiRunStepFile;
use App\Models\GeniePost;
use App\Models\WorkspaceFile;
use Illuminate\Http\Resources\Json\JsonResource;
use Inovector\Mixpost\Facades\SocialProviderManager;
use Inovector\Mixpost\Facades\WorkspaceManager;

/**
 * What the Mixpost editor's Genie panel needs about a post Genie produced.
 *
 * Each image slot joins the lineage (which file, which media) with the post's original version
 * (is that media still in it) and the slot's generation history (brief, feedback, mode,
 * superseded versions).
 *
 * @mixin GeniePost
 */
class GeniePostResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->uuid,
            'status' => $this->status,
            'status_title' => $this->status?->title(),
            'error' => $this->error,
            'busy' => $this->isBusy(),
            'has_images' => $this->hasImages(),
            'idea' => $this->idea ? $this->idea() : null,
            'mixpost_post' => $this->mixpostPost?->uuid,
            'actions' => $this->actions(),
            'images' => $this->images(),
        ];
    }

    /**
     * The idea the post is written from, with its labels pre-rendered: the Mixpost editor page
     * has none of the ideas page's enum props to translate them itself.
     *
     * @return array<string, mixed>
     */
    protected function idea(): array
    {
        $idea = $this->idea;

        $providers = SocialProviderManager::providers();

        return [
            'id' => $idea->uuid,
            'title' => $idea->title,
            'url' => route('genie.ideas.edit', ['workspace' => WorkspaceManager::current()->uuid, 'idea' => $idea->uuid]),
            'goal' => $idea->goal,
            'suggested_content' => $idea->suggested_content,
            'multimedia_suggestion' => $idea->multimedia_suggestion,
            'production_instructions' => $idea->production_instructions,
            'images' => $idea->images ?? [],
            'publish_to' => array_map(fn (string $key) => [
                'provider' => $key,
                'name' => isset($providers[$key]) ? $providers[$key]::name() : $key,
            ], $idea->publish_to ?? []),
            'content_pillar' => $idea->content_pillar,
            'format_label' => $idea->post_format ? __('genie.post_format_'.$idea->post_format->title()) : null,
            'stage_label' => $idea->funnel_stage ? __('genie.funnel_stage_'.$idea->funnel_stage->title()) : null,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function images(): array
    {
        $images = [];

        foreach ($this->resource->slots() as $slot) {
            // The lineage names the slot (through its file's row); the slot's current row is read by
            // run step and position, so a just-persisted revision is current even before the lineage
            // has been updated to it.
            $lineage = $slot['file_id']
                ? WorkspaceFile::with('runStepFile')->find($slot['file_id'])?->runStepFile
                : null;

            $rows = $lineage
                ? AiRunStepFile::with('file')
                    ->where('run_step_id', $lineage->run_step_id)
                    ->where('position', $slot['position'])
                    ->orderByDesc('id')
                    ->get()
                : collect();

            $current = $rows->first(fn (AiRunStepFile $row) => $row->superseded_at === null) ?? $rows->first();

            $history = $current ? $rows->reject(fn (AiRunStepFile $row) => $row->is($current))->values() : collect();

            $revising = $rows->contains(fn (AiRunStepFile $row) => $row->revising_at !== null);

            $images[] = [
                'position' => $slot['position'],
                'media_id' => $slot['media_id'],
                'in_post' => $slot['in_post'],
                'media_exists' => $slot['media_exists'],
                'file' => $this->file($current),
                'brief' => $current?->brief,
                'feedback' => $current?->feedback,
                'mode' => $current?->mode,
                'revising' => $revising,
                'revision_error' => $current?->revision_error,
                'history' => $history->map(fn (AiRunStepFile $version) => [
                    'id' => $version->id,
                    'created_at' => $version->created_at?->format('Y-m-d H:i:s'),
                    'mode' => $version->mode,
                    'feedback' => $version->feedback,
                    'revision_error' => $version->revision_error,
                    'file' => $this->file($version),
                ])->values()->all(),
            ];
        }

        return $images;
    }

    /**
     * @return array{id: string, url: string, name: string}|null
     */
    protected function file(?AiRunStepFile $row): ?array
    {
        $file = $row?->file;

        return $file ? ['id' => $file->uuid, 'url' => $file->getUrl(), 'name' => $file->name] : null;
    }
}
