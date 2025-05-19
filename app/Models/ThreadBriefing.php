<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThreadBriefing extends Model
{

    public $table = 'genie_thread_briefings';

    protected $fillable = [
        'thread_id',
        'briefing_id',
    ];

}
