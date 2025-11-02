<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrePostRunResponse extends Model
{
    public $table = 'genie_pre_post_run_responses';

    protected $fillable = [
        'pre_post_id',
        'run_response_id'
    ];

    /**
     * @return BelongsTo
     */
    public function prePost(): BelongsTo
    {
        return $this->belongsTo(PrePost::class);
    }

    /**
     * @return BelongsTo
     */
    public function runResponse(): BelongsTo
    {
        return $this->belongsTo(RunResponse::class);
    }
}
