<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RunDraftResponse extends Model
{

    public $table = 'genie_run_draft_responses';

    protected $fillable = [
        'run_draft_id',
        'run_response_id',
    ];

    /**
     * @return BelongsTo
     */
    public function runDraft(): BelongsTo
    {
        return $this->belongsTo(RunDraft::class);
    }

    /**
     * @return BelongsTo
     */
    public function runResponse(): BelongsTo
    {
        return $this->BelongsTo(RunResponse::class);
    }
}
