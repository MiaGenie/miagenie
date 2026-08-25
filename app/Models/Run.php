<?php

namespace App\Models;

use App\Enums\RunStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'conversation_id',
        'status',
    ];

    protected $casts = [
        'status' => RunStatus::class,
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(Rule::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function runResponses(): HasMany
    {
        return $this->hasMany(RunResponse::class);
    }

    public function runFieldIterators(): HasManyThrough
    {
        return $this->hasManyThrough(RunFieldIterator::class, RunResponse::class);
    }

    public function strategy(): HasOne
    {
        return $this->HasOne(Strategy::class);
    }

    public function runStrategy(): HasOne
    {
        return $this->HasOne(RunStrategy::class);
    }

    public function runIdeas(): HasMany
    {
        return $this->hasMany(RunIdea::class);
    }

    public function runDrafts(): HasMany
    {
        return $this->hasMany(RunDraft::class);
    }

    public function runBriefing(): HasOne
    {
        return $this->HasOne(RunBriefing::class);
    }
}
