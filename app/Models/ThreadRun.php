<?php

namespace App\Models;

use App\Enums\ThreadRunStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Inovector\Mixpost\Concerns\Model\HasUuid;

class ThreadRun extends Model
{
    use HasUuid;

    public $table = 'genie_thread_runs';

    protected $fillable = [
        'uuid',
        'thread_id',
        'step_id',
        'run_provider_id',
        'status',
        'error',
        'error_details',
        'incomplete_details',
        'message',
    ];

    protected $casts = [
        'status' => ThreadRunStatus::class
    ];

    /**
     * @return BelongsTo
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class, 'id', 'thread_id');
    }
}
