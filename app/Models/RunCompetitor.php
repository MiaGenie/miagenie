<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RunCompetitor extends Model
{

    public $table = 'genie_run_competitors';

    protected $fillable = [
        'run_response_id',
        'competitor_id',
    ];

    /**
     * @return BelongsTo
     */
    public function response(): BelongsTo
    {
        return $this->belongsTo(RunResponse::class, 'run_response_id');
    }

    /**
     * @return BelongsTo
     */
    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class, 'competitor_id');
    }


}
