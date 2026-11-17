<?php

namespace App\Models;

use App\Enums\GenieSyncAction;
use App\Enums\GenieType;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'run_step_id',
        'request',
        'response',
        'duration',
        'input_tokens',
        'output_tokens',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'type' => GenieType::class,
        'action' => GenieSyncAction::class,
        'request' => AsCollection::class,
        'response' => AsCollection::class,
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
    ];

    /**
     * The run step this call was made for, when it belongs to a run.
     */
    public function runStep(): BelongsTo
    {
        return $this->belongsTo(AiRunStep::class, 'run_step_id');
    }
}
