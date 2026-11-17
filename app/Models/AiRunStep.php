<?php

namespace App\Models;

use App\Enums\Modality;
use App\Enums\RunResponseError;
use App\Enums\RunStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Inovector\Mixpost\Concerns\Model\HasUuid;

/**
 * One turn of a run.
 *
 * The transcript and provider meta are not duplicated here — they belong to the conversation
 * message this step points at — and neither are token counts, which live on the step's log
 * entries, one per model call (see withTokenCounts()). What is kept is the domain result: the
 * structured `output` that gets mapped onto strategy fields, and how the turn ended.
 */
class AiRunStep extends Model
{
    use HasUuid;

    protected $table = 'genie_ai_run_steps';

    protected $fillable = [
        'uuid',
        'run_id',
        'step_id',
        'position',
        'modality',
        'status',
        'claimed_by',
        'invocation_id',
        'message_id',
        'output',
        'error',
        'error_details',
        'duration',
    ];

    protected $casts = [
        'modality' => Modality::class,
        'status' => RunStatus::class,
        'error' => RunResponseError::class,
        'output' => 'array',
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(AiRun::class, 'run_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(RuleStep::class, 'step_id');
    }

    /**
     * The ideas this turn produced, when it belongs to an ideas run.
     */
    public function ideas(): HasMany
    {
        return $this->hasMany(Idea::class, 'run_step_id');
    }

    /**
     * Every model call made for this step, newest first: one for a text step, one per brief and
     * per revision for an image step.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(Log::class, 'run_step_id')->latest('id');
    }

    /**
     * Add the tokens every call of the step consumed, as `input_tokens` and `output_tokens`:
     * retries, regenerations and slot revisions logged on the step included. Null when no entry
     * of the step knows its counts.
     */
    public function scopeWithTokenCounts(Builder $query): Builder
    {
        return $query
            ->withSum('logs as input_tokens', 'input_tokens')
            ->withSum('logs as output_tokens', 'output_tokens');
    }

    public function review(): HasOne
    {
        return $this->hasOne(AiRunStepReview::class, 'run_step_id')->latestOfMany();
    }

    /**
     * The files this turn produced, one row per slot and version.
     *
     * An image step fills one slot per brief; a regenerated slot keeps its superseded rows. Use
     * `images()->current()` for what fills each slot now.
     */
    public function images(): HasMany
    {
        return $this->hasMany(AiRunStepFile::class, 'run_step_id')
            ->oldest('position')
            ->oldest('id');
    }

    public function files(): BelongsToMany
    {
        return $this->belongsToMany(
            WorkspaceFile::class,
            'genie_ai_run_step_files',
            'run_step_id',
            'workspace_file_id',
        )->withPivot([
            'position',
            'brief',
            'prompt',
            'feedback',
            'mode',
            'parent_file_id',
            'superseded_at',
            'revising_at',
            'revision_error',
        ])->withTimestamps();
    }

    /**
     * The conversation message this turn produced, when there is one.
     */
    public function message(): ?object
    {
        if (! $this->message_id) {
            return null;
        }

        return \DB::table(config('ai.conversations.tables.messages', 'agent_conversation_messages'))
            ->find($this->message_id);
    }

    /**
     * The prompt this turn answered.
     *
     * A step stores only the message it wrote, so the question is the newest one asked before it in
     * the same conversation. Message ids are time ordered, which is what makes "before" a plain
     * comparison.
     */
    public function promptMessage(): ?object
    {
        $answer = $this->message();

        if (! $answer) {
            return null;
        }

        return \DB::table(config('ai.conversations.tables.messages', 'agent_conversation_messages'))
            ->where('conversation_id', $answer->conversation_id)
            ->where('role', 'user')
            ->where('id', '<', $answer->id)
            ->orderByDesc('id')
            ->first();
    }

    public function isAwaitingReview(): bool
    {
        return $this->status === RunStatus::PENDING_REVIEW;
    }
}
