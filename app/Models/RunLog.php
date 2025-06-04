<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;
use Inovector\Mixpost\Concerns\Model\HasUuid;

class RunLog extends Model
{
    use HasUuid;

    public $table = 'genie_run_logs';

    protected $fillable = [
        'uuid',
        'type',
        'action',
        'request',
        'response'
    ];

    protected $casts = [
        'request' => AsCollection::class,
        'response' => AsCollection::class,
    ];
}
