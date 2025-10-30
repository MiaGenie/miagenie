<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RunFieldIterator extends Model
{

    public $table = 'genie_run_field_iterators';

    protected $fillable = [
        'run_response_id',
        'field_id',
        'field_index',
    ];

    /**
     * @return BelongsTo
     */
    public function response(): BelongsTo
    {
        return $this->belongsTo(RunResponse::class);
    }

    /**
     * @return BelongsTo
     */
    public function versionField(): BelongsTo
    {
        return $this->belongsTo(VersionField::class);
    }


}
