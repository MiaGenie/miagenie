<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RunIdeaResponse extends Model
{

    public $table = 'genie_run_idea_responses';

    protected $fillable = [
        'run_idea_id',
        'run_response_id',
    ];

    /**
     * @return BelongsTo
     */
    public function runIdea(): BelongsTo
    {
        return $this->belongsTo(RunIdea::class);
    }

    /**
     * @return BelongsTo
     */
    public function runResponse(): BelongsTo
    {
        return $this->BelongsTo(RunResponse::class);
    }
}
