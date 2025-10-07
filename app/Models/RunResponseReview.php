<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RunResponseReview extends Model
{

    public $table = 'genie_run_response_reviews';

    protected $fillable = [
        'id',
        'run_response_id',
        'original',
        'reviewed',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'original' => 'array',
        'reviewed' => 'array'
    ];

    /**
     * @return BelongsTo
     */
    public function runResponse(): BelongsTo
    {
        return $this->belongsTo(RunResponse::class, 'run_response_id');
    }
}
