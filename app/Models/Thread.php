<?php

namespace App\Models;

use App\Enums\RunStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Inovector\Mixpost\Concerns\Model\HasUuid;
use Inovector\Mixpost\Models\Workspace;

class Thread extends Model
{
    use HasUuid;

    public $table = 'genie_threads';

    protected $fillable = [
        'uuid',
        'workspace_id',
        'rule_id',
        'thread_provider_id',
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
        return $this->belongsTo(Rule::class, 'rule_id');
    }

    /**
     * @return BelongsTo
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    /**
     * @return HasMany
     */
    public function runs(): HasMany
    {
        return $this->hasMany(ThreadRun::class, 'thread_id');
    }
}
