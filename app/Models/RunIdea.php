<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RunIdea extends Model
{

    public $table = 'genie_run_ideas';

    protected $fillable = [
        'run_id',
        'idea_id',
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
    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    /**
     * @return HasMany
     */
    public function runIdeaResponses(): HasMany
    {
        return $this->hasMany(RunIdeaResponse::class);
    }
}
