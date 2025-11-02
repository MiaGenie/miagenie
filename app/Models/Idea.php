<?php

namespace App\Models;

use App\Concerns\Controller\GenieFields;
use App\Enums\FunnelStage;
use App\Enums\IdeaSource;
use App\Enums\IdeaStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Inovector\Mixpost\Concerns\Model\HasUuid;
use Inovector\Mixpost\Concerns\OwnedByWorkspace;

class Idea extends Model
{
    use HasUuid;
    use OwnedByWorkspace;
    use GenieFields;

    protected $fillable = [
        'workspace_id',
        'theme',
        'description',
        'status',
        'source',
        'run_response_id',
        'funnel_stage',
        'content_pillar'
    ];

    /**
     * @var string[]
     */
    protected array $genie_fields = [
        'theme',
        'description',
    ];

    /**
     * @var string
     */
    protected $table = 'genie_ideas';

    /**
     * @var string[]
     */
    protected $casts = [
        'status' => IdeaStatus::class,
        'source' => IdeaSource::class,
        'funnel_stage' => FunnelStage::class,
    ];

    /**
     * @return BelongsTo
     */
    public function strategy(): BelongsTo
    {
        return $this->belongsTo(Strategy::class);
    }

    /**
     * @return HasMany
     */
    public function drafts(): HasMany
    {
        return $this->HasMany(Draft::class);
    }

    /**
     * @return BelongsTo
     */
    public function runResponse(): BelongsTo
    {
        return $this->belongsTo(RunResponse::class);
    }

}