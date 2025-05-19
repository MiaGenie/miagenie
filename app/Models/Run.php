<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Inovector\Mixpost\Concerns\Model\HasUuid;

class Run extends Model
{
    use HasUuid;

    public $table = 'genie_thread_runs';

    protected $fillable = [
        'uuid',
        'thread_id',
        'step_id',
        'max_completion_tokens',
        'max_prompt_tokens',
        'status',
        'status_provider',
        'error_provider',
        'incomplete_details_provider',
        'message',
    ];
}
