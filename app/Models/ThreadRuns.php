<?php

namespace App\Models;

use App\Enums\RuleType;
use Illuminate\Database\Eloquent\Model;
use Inovector\Mixpost\Concerns\Model\HasUuid;

class ThreadRuns extends Model
{
    use HasUuid;

    public $table = 'genie_threads_runs';

    protected $fillable = [
        'uuid',
        'thread_id',
        'step_id',
        'status',
        'status_provider',
        'message',
    ];
}
