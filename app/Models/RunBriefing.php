<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RunBriefing extends Model
{

    public $table = 'genie_run_briefings';

    protected $fillable = [
        'run_id',
        'briefing_id',
    ];

    /**
     * @return BelongsTo
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(Run::class, 'run_id');
    }
}
