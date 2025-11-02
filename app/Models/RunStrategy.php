<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RunStrategy extends Model
{

    public $table = 'genie_run_strategy';

    protected $fillable = [
        'run_id',
        'strategy_id',
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
    public function strategy(): BelongsTo
    {
        return $this->belongsTo(Strategy::class);
    }
}
