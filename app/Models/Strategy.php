<?php

namespace App\Models;

use App\Enums\StrategyStatus;
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
     */
    public function aiRun(): HasOne
    {
        return $this->hasOne(AiRun::class, 'strategy_id');
    }

    public function ideas(): HasMany
    {
        return $this->hasMany(Idea::class);
    }
}
