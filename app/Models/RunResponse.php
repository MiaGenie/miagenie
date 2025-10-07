<?php

namespace App\Models;

use App\Enums\RunResponseError;
use App\Enums\RunResponseStatus;
use App\Enums\RunStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Inovector\Mixpost\Concerns\Model\HasUuid;

class RunResponse extends Model
{
    use HasUuid;

    public $table = 'genie_run_responses';

    protected $fillable = [
        'id',
        'uuid',
        'run_id',
        'step_id',
        'response_provider_id',
        'provider_status',
        'error',
        'error_details',
        'incomplete_details',
        'output',
        'output_text',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'error' => RunResponseError::class,
        'provider_status' => RunResponseStatus::class,
        'output' => 'array',
        'status' => RunStatus::class,
    ];

    /**
     * @return BelongsTo
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(Run::class, 'run_id');
    }

    /**
     * @return BelongsTo
     */
    public function step(): BelongsTo
    {
        return $this->belongsTo(RuleStep::class, 'step_id');
    }

    /**
     * @return HasOne
     */
    public function runCompetitor(): HasOne
    {
        return $this->HasOne(RunCompetitor::class, 'run_response_id');
    }

    /**
     * @return HasOne
     */
    public function runResponseReview(): HasOne
    {
        return $this->HasOne(RunResponseReview::class, 'run_response_id');
    }
}
