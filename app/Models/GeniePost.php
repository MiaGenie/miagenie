<?php

namespace App\Models;

use App\Enums\GeniePostStatus;
use App\Enums\PostFormat;
use App\Enums\RuleSubType;
use App\Enums\RunStatus;
use App\Genie\Posts\PostsStepGate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Inovector\Mixpost\Concerns\Model\HasUuid;
use Inovector\Mixpost\Concerns\OwnedByWorkspace;
use Inovector\Mixpost\Models\Media;
use Inovector\Mixpost\Models\Post;
use Inovector\Mixpost\Models\PostVersion;

/**
 * Genie's record of a Mixpost post it produces from an idea.
 *
 * The Mixpost draft holds the content and is the truth for what the post contains; this row
 * tracks the production. `images` is lineage — slot, generated file, the media row it was copied
 * to — and slots() joins it with the post's original version to say which images are still in.
 */
class GeniePost extends Model
{
    use HasUuid;
    use OwnedByWorkspace;
    use SoftDeletes;

    protected $table = 'genie_posts';

    protected $fillable = [
        'uuid',
        'workspace_id',
        'idea_id',
        'run_id',
        'post_id',
        'post_format',
        'images',
        'status',
        'error',
    ];

    protected $casts = [
        'post_format' => PostFormat::class,
        'status' => GeniePostStatus::class,
        'images' => 'array',
    ];

    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class, 'idea_id');
    }

    /**
     * The run that wrote the post last.
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(AiRun::class, 'run_id');
    }

    public function mixpostPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * The posts rule that produced this post, when it still exists.
     */
    public function rule(): ?Rule
    {
        return $this->run?->rule;
    }

    /**
     * The rule's step for one posts sub type and this post's format — the format-specific step
     * when the rule has one, else the generic one — or null when the rule has none, in which case
     * the action that step is the prompt for is not offered. There is no fallback to another sub type.
     */
    public function stepFor(RuleSubType $subType): ?RuleStep
    {
        $rule = $this->rule();

        return $rule ? PostsStepGate::select($rule->steps, $subType, $this->post_format) : null;
    }

    /**
     * Which regeneration actions the rule supports.
     *
     * @return array{revise_text: bool, fix: bool, rethink: bool}
     */
    public function actions(): array
    {
        return [
            'revise_text' => $this->stepFor(RuleSubType::POSTS_REVISE_TEXT) !== null,
            'fix' => $this->stepFor(RuleSubType::POSTS_IMAGE_FIX) !== null,
            'rethink' => $this->stepFor(RuleSubType::POSTS_IMAGE_RETHINK) !== null,
        ];
    }

    /**
     * The version every account publishes unless it has one of its own; the only one Genie writes.
     *
     * Read fresh each time: Mixpost recreates the versions on every save from the editor.
     */
    public function originalVersion(): ?PostVersion
    {
        $post = $this->mixpostPost;

        if (! $post) {
            return null;
        }

        if ($post->relationLoaded('versions')) {
            return $post->versions->first(
                fn (PostVersion $version) => $version->is_original && (int) $version->account_id === 0
            );
        }

        return $post->versions()
            ->where('is_original', true)
            ->where('account_id', 0)
            ->first();
    }

    /**
     * Whether a run or an image revision is working on this post right now.
     */
    public function isBusy(): bool
    {
        $running = AiRun::withoutWorkspace()
            ->where('parameters->post', $this->id)
            ->whereIn('status', [RunStatus::OPEN, RunStatus::RUNNING])
            ->exists();

        if ($running) {
            return true;
        }

        $fileIds = array_filter(array_column($this->images ?? [], 'file_id'));

        // Not `current()`: a superseded parent keeps `revising_at` until the revision job has copied
        // the new file where the post reads it, so the post stays busy through that last stretch.
        return $fileIds !== [] && AiRunStepFile::whereIn('workspace_file_id', $fileIds)
            ->whereNotNull('revising_at')
            ->exists();
    }

    public function hasImages(): bool
    {
        return $this->post_format?->imageRange() !== null;
    }

    /**
     * The lineage entry of one slot.
     *
     * @return array{position: int, file_id: int|null, media_id: string|null}|null
     */
    public function slot(int $position): ?array
    {
        foreach ($this->images ?? [] as $slot) {
            if ((int) ($slot['position'] ?? 0) === $position) {
                return $slot;
            }
        }

        return null;
    }

    /**
     * Every slot with what the post says about it: whether its media is still in the original
     * version, and whether the media row still exists at all.
     *
     * @param  list<string>|null  $existingMediaIds  the media rows known to exist; looked up when not given
     * @return list<array{position: int, file_id: int|null, media_id: string|null, in_post: bool, media_exists: bool}>
     */
    public function slots(?array $existingMediaIds = null): array
    {
        $inPost = $this->versionMediaIds();

        $mediaIds = array_filter(array_column($this->images ?? [], 'media_id'));

        $existing = $existingMediaIds !== null
            ? new Collection(array_map('strval', $existingMediaIds))
            : ($mediaIds === []
                ? new Collection
                : Media::withoutWorkspace()->whereIn('id', $mediaIds)->pluck('id')->map(fn ($id) => (string) $id));

        $slots = [];

        foreach ($this->images ?? [] as $slot) {
            $mediaId = isset($slot['media_id']) ? (string) $slot['media_id'] : null;

            $slots[] = [
                'position' => (int) ($slot['position'] ?? 0),
                'file_id' => $slot['file_id'] ?? null,
                'media_id' => $mediaId,
                'in_post' => $mediaId !== null && in_array($mediaId, $inPost, true),
                'media_exists' => $mediaId !== null && $existing->contains($mediaId),
            ];
        }

        usort($slots, fn (array $a, array $b) => $a['position'] <=> $b['position']);

        return $slots;
    }

    /**
     * The generated media ids in slot order, whether or not they are still in the post.
     *
     * @return list<string>
     */
    public function mediaIds(): array
    {
        $slots = array_values(array_filter($this->images ?? [], fn (array $slot) => isset($slot['media_id'])));

        usort($slots, fn (array $a, array $b) => (int) ($a['position'] ?? 0) <=> (int) ($b['position'] ?? 0));

        return array_map(fn (array $slot) => (string) $slot['media_id'], $slots);
    }

    /**
     * The media ids the original version carries right now, as strings.
     *
     * @return list<string>
     */
    public function versionMediaIds(): array
    {
        $content = $this->originalVersion()?->content ?? [];

        return array_values(array_map('strval', $content[0]['media'] ?? []));
    }
}
