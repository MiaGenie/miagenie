<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RunDraft extends Model
{

    public $table = 'genie_run_drafts';

    protected $fillable = [
        'run_id',
        'draft_id',
    ];

    /**
     * @return BelongsTo
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(Run::class);
    }

    /**
     * @return BelongsTo
     */
    public function draft(): BelongsTo
    {
        return $this->belongsTo(Draft::class);
    }

    /**
     * @return HasMany
     */
    public function runDraftResponses(): HasMany
    {
        return $this->hasMany(RunDraftResponse::class);
    }
}
