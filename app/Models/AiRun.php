<?php

namespace App\Models;

use App\Enums\RunStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Inovector\Mixpost\Concerns\Model\HasUuid;
use Inovector\Mixpost\Concerns\OwnedByWorkspace;

/**
 * One generation run: a rule applied to a briefing, producing a strategy.
 *
 * The run owns the AI SDK conversation its steps share, which is what lets each step be prompted
 * with what the earlier ones produced.
 */
class AiRun extends Model
{
    use HasUuid;
    use OwnedByWorkspace;

    protected $table = 'genie_ai_runs';

    protected $fillable = [
        'uuid',
        'workspace_id',
        'rule_id',
        'briefing_id',
        'strategy_id',
        'parameters',
        'conversation_id',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'status' => RunStatus::class,
        'parameters' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(Rule::class);
    }

    public function briefing(): BelongsTo
    {
        return $this->belongsTo(Briefing::class);
    }

    public function strategy(): BelongsTo
    {
        return $this->belongsTo(Strategy::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(AiRunStep::class, 'run_id')->oldest('position');
    }

    /**
     * Every model call made for the run, through the steps it was logged on.
     */
    public function logs(): HasManyThrough
    {
        return $this->hasManyThrough(Log::class, AiRunStep::class, 'run_id', 'run_step_id');
    }

    /**
     * Add the tokens every call of the run consumed, as `input_tokens` and `output_tokens`:
     * retries, caption regenerations and image fixes / rethinks included, since each is logged on
     * one of the run's steps. Null when no entry of the run knows its counts.
     */
    public function scopeWithTokenCounts(Builder $query): Builder
    {
        return $query
            ->withSum('logs as input_tokens', 'input_tokens')
            ->withSum('logs as output_tokens', 'output_tokens');
    }

    /**
     * The step the run is on, which is the newest one regardless of how it ended.
     */
    public function currentStep(): ?AiRunStep
    {
        return $this->steps()->reorder()->latest('position')->first();
    }

    /**
     * The conversation messages behind this run, in the order they were written.
     */
    public function conversationMessages(): iterable
    {
        if (! $this->conversation_id) {
            return [];
        }

        return \DB::table(config('ai.conversations.tables.messages', 'agent_conversation_messages'))
            ->where('conversation_id', $this->conversation_id)
            ->orderBy('id')
            ->get();
    }
}
