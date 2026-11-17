<?php

namespace App\Models;

use App\Enums\StrategyStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Inovector\Mixpost\Concerns\Model\HasUuid;
use Inovector\Mixpost\Concerns\OwnedByWorkspace;

class Strategy extends Model
{
    use HasUuid;
    use OwnedByWorkspace;

    protected $fillable = [
        'workspace_id',
        'content',
        'status',
    ];

    /**
     * @var string
     */
    protected $table = 'genie_strategies';

    /**
     * @var string[]
     */
    protected $guarded = [
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'content' => 'array',
        'status' => StrategyStatus::class,
    ];

    /**
     * The run that produced this strategy; the run owns the link through `genie_ai_runs.strategy_id`.
     *
     * Ideas and posts runs carry the strategy they were built from in the same column, so the
     * strategy has many runs pointing at it. Its own is the first: it is created together with the
     * strategy, and the others can only follow once the strategy is approved.
     */
    public function aiRun(): HasOne
    {
        return $this->hasOne(AiRun::class, 'strategy_id')->oldest('id');
    }

    public function ideas(): HasMany
    {
        return $this->hasMany(Idea::class);
    }

    /**
     * Strategies the customer approved: the only ones ideas are generated from, so a newer
     * strategy still being generated or reviewed never takes their place.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', StrategyStatus::APPROVED);
    }

    /**
     * Whether this strategy is waiting for the customer to start its generation: an admin asked
     * for a new strategy, and no run has been created for it yet.
     */
    public function awaitsGeneration(): bool
    {
        return $this->status === StrategyStatus::OPEN && ! $this->hasRun();
    }

    /**
     * Whether a run is producing this strategy and has not settled yet.
     */
    public function isGenerating(): bool
    {
        return $this->status->isGenerating() && $this->hasRun();
    }

    /**
     * The run is read past the workspace scope so the answer holds in the admin panel, where no
     * workspace is current; a strategy and its run always share a workspace.
     */
    protected function hasRun(): bool
    {
        if ($this->relationLoaded('aiRun')) {
            return $this->aiRun !== null;
        }

        return $this->aiRun()->withoutWorkspace()->exists();
    }
}
