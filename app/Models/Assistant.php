<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
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
        'vector_id',
        'response_format',
        'json_schema',
        'temperature',
        'top_p'
    ];

    protected $casts = [
        'assistant_type' => AssistantType::class,
    ];
}
