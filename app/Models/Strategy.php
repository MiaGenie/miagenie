<?php

namespace App\Models;

use App\Enums\StrategyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'status'
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
        'status' => StrategyStatus::class
    ];

    /**
     * @return BelongsTo
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(Run::class);
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
    public function ideas(): HasMany
    {
        return $this->hasMany(Idea::class);
    }
}
