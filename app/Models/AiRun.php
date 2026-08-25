<?php

namespace App\Models;

use App\Enums\RunStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Inovector\Mixpost\Concerns\Model\HasUuid;
use Inovector\Mixpost\Concerns\OwnedByWorkspace;
use Inovector\Mixpost\Models\Workspace;

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
        'conversation_id',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'status' => RunStatus::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

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
