<?php

namespace App\Models;

use App\Enums\Modality;
use App\Enums\RunResponseError;
use App\Enums\RunStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Inovector\Mixpost\Concerns\Model\HasUuid;

/**
 * One turn of a run.
 *
 * The transcript, token usage and provider meta are not duplicated here — they belong to the
 * conversation message this step points at. What is kept is the domain result: the structured
 * `output` that gets mapped onto strategy fields, and how the turn ended.
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
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(AiRun::class, 'run_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(RuleStep::class, 'step_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(AiRunStepReview::class, 'run_step_id')->latestOfMany();
    }

    public function files(): BelongsToMany
    {
        return $this->belongsToMany(
            WorkspaceFile::class,
            'genie_ai_run_step_files',
            'run_step_id',
            'workspace_file_id',
        )->withTimestamps();
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
