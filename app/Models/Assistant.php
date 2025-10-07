<?php

namespace App\Models;

use App\Enums\GenieSyncStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\AssistantType;
use Inovector\Mixpost\Concerns\Model\HasUuid;

class Assistant extends Model
{
    use HasUuid;
    use SoftDeletes;

    public $table = 'genie_assistants';

    protected $fillable = [
        'uuid',
        'name',
        'assistant_type',
        'description',
        'instructions',
        'model',
        'assistant_provider_id',
        'vector_id',
        'response_format',
        'json_schema',
        'temperature',
        'top_p',
        'reasoning_effort',
        'status'
    ];

    protected $casts = [
        'assistant_type' => AssistantType::class,
        'status' => GenieSyncStatus::class,
    ];
}
