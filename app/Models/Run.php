<?php

namespace App\Models;

use App\Enums\RunStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Inovector\Mixpost\Concerns\Model\HasUuid;
use Inovector\Mixpost\Models\Workspace;

class Run extends Model
{
    use HasUuid;

    public $table = 'genie_runs';

    protected $fillable = [
        'id',
        'uuid',
        'workspace_id',
        'rule_id',
        'status',
    ];

    protected $casts = [
        'status' => RunStatus::class
    ];

    /**
     * @return BelongsTo
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(Rule::class);
    }

    /**
     * @return BelongsTo
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return HasMany
     */
    public function runResponses(): HasMany
    {
        return $this->hasMany(RunResponse::class);
    }

    /**
     * @return HasManyThrough
     */
    public function runFieldIterators(): HasManyThrough
    {
        return $this->hasManyThrough(RunFieldIterator::class, RunResponse::class);
    }

    /**
     * @return HasOne
     */
    public function strategy(): HasOne
    {
        return $this->HasOne(Strategy::class);
    }

    /**
     * @return HasOne
     */
    public function runStrategy(): HasOne
    {
        return $this->HasOne(RunStrategy::class);
    }

    /**
     * @return HasMany
     */
    public function runIdeas(): HasMany
    {
        return $this->hasMany(RunIdea::class);
    }

    /**
     * @return HasMany
     */
    public function runDrafts(): HasMany
    {
        return $this->hasMany(RunDraft::class);
    }

    /**
     * @return HasOne
     */
    public function briefing(): HasOne
    {
        return $this->HasOne(Briefing::class, 'run_id');
    }
}
