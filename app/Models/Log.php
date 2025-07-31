<?php

namespace App\Models;

use App\Enums\GenieSyncAction;
use App\Enums\GenieType;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;
use Inovector\Mixpost\Concerns\Model\HasUuid;

class Log extends Model
{
    use HasUuid;

    public $table = 'genie_logs';

    protected $fillable = [
        'id',
        'uuid',
        'type',
        'action',
        'request',
        'response',
        'duration',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'type' => GenieType::class,
        'action' => GenieSyncAction::class,
        'request' => AsCollection::class,
        'response' => AsCollection::class,
    ];
}
