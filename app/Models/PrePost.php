<?php

namespace App\Models;

use App\Concerns\Controller\GenieFields;
use App\Enums\PrePostStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Inovector\Mixpost\Concerns\Model\HasUuid;
use Inovector\Mixpost\Concerns\OwnedByWorkspace;

class PrePost extends Model
{
    use HasUuid;
    use OwnedByWorkspace;
    use GenieFields;

    /**
     * @var string
     */
    protected $table = 'genie_pre_posts';

    protected $fillable = [
        'workspace_id',
        'draft_id',
        'caption',
        'status'
    ];

    /**
     * @var string[]
     */
    protected array $genie_fields = [
        'caption'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'status' => PrePostStatus::class,
    ];

    /**
     * @return BelongsTo
     */
    public function draft(): BelongsTo
    {
        return $this->belongsTo(Draft::class);
    }

    /**
     * @return HasMany
     */
    public function prePostRunResponses(): HasMany
    {
        return $this->HasMany(PrePostRunResponse::class);
    }

    /**
     * @return HasManyThrough
     */
    public function runResponses(): HasManyThrough
    {
        return $this->HasManyThrough(RunResponse::class, PrePostRunResponse::class);
    }
}
