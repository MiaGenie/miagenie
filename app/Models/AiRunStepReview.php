<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What a reviewer changed on a step before letting the run continue.
 */
class AiRunStepReview extends Model
{
    protected $table = 'genie_ai_run_step_reviews';

    protected $fillable = [
        'run_step_id',
        'reviewed_by',
        'original',
        'reviewed',
    ];

    protected $casts = [
        'original' => 'array',
        'reviewed' => 'array',
    ];

    public function runStep(): BelongsTo
    {
        return $this->belongsTo(AiRunStep::class, 'run_step_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
