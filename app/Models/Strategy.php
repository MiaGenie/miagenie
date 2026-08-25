<?php

namespace App\Models;

use App\Enums\StrategyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Inovector\Mixpost\Concerns\Model\HasUuid;
use Inovector\Mixpost\Concerns\OwnedByWorkspace;
use Inovector\Mixpost\Models\Workspace;

class Strategy extends Model
{
    use HasUuid;
    use OwnedByWorkspace;

    protected $fillable = [
        'run_id',
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

    public function run(): BelongsTo
    {
        return $this->belongsTo(Run::class);
    }

    /**
     * The run that produced this strategy on the current pipeline.
     *
     * The legacy `run()` above points the other way — the strategy holds the foreign key — while
     * the new run owns the link through `genie_ai_runs.strategy_id`.
     */
    public function aiRun(): HasOne
    {
        return $this->hasOne(AiRun::class, 'strategy_id');
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function ideas(): HasMany
    {
        return $this->hasMany(Idea::class);
    }
}
