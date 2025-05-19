<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RunCompetitor extends Model
{

    public $table = 'genie_run_competitors';

    protected $fillable = [
        'run_id',
        'competitor_id',
    ];

}
