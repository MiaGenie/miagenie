<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One file a run step produced, in one of its slots.
 *
 * The row records the slot (`position`) and what was asked for that file (`brief`, the `prompt`
 * actually sent, `feedback`), so a later regeneration can be read back and reproduced. A slot
 * keeps its history: the file that was replaced gets `superseded_at`, the replacement points at
 * it through `parent_file_id` and says how it was made in `mode`. The current file of a slot is
 * the row without `superseded_at`.
 */
class AiRunStepFile extends Model
{
    protected $table = 'genie_ai_run_step_files';

    protected $fillable = [
        'run_step_id',
        'workspace_file_id',
        'position',
        'brief',
        'prompt',
        'feedback',
        'mode',
        'parent_file_id',
        'superseded_at',
        'revising_at',
        'revision_error',
    ];

    protected $casts = [
        'position' => 'integer',
        'superseded_at' => 'datetime',
        'revising_at' => 'datetime',
    ];

    public function runStep(): BelongsTo
    {
        return $this->belongsTo(AiRunStep::class, 'run_step_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(WorkspaceFile::class, 'workspace_file_id');
    }

    /**
     * The file this one replaced, when it is a regeneration.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(WorkspaceFile::class, 'parent_file_id');
    }

    /**
     * Only the file currently filling each slot.
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereNull('superseded_at');
    }

    public function isRevising(): bool
    {
        return $this->revising_at !== null && $this->superseded_at === null;
    }
}
