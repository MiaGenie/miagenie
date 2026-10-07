<?php

namespace App\Models;

use App\Concerns\Controller\GenieFields;
use App\Enums\FunnelStage;
use App\Enums\IdeaSource;
use App\Enums\IdeaStatus;
use App\Enums\PostFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Inovector\Mixpost\Concerns\Model\HasUuid;
use Inovector\Mixpost\Concerns\OwnedByWorkspace;

/**
 * One content idea, generated for a post format or written by hand.
 *
 * Everything a post is later produced from lives here: the goal, the suggested content, the
 * multimedia suggestion and the production instructions, together with the format, content pillar
 * and funnel stage the idea was generated for.
 */
class Idea extends Model
{
    use GenieFields;
    use HasUuid;
    use OwnedByWorkspace;

    protected $fillable = [
        'workspace_id',
        'strategy_id',
        'run_step_id',
        'title',
        'goal',
        'suggested_content',
        'multimedia_suggestion',
        'production_instructions',
        'images',
        'publish_to',
        'status',
        'source',
        'funnel_stage',
        'post_format',
        'content_pillar',
    ];

    /**
     * @var string[]
     */
    protected array $genie_fields = [
        'title',
        'goal',
        'suggested_content',
        'multimedia_suggestion',
        'production_instructions',
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
        'post_format' => PostFormat::class,
        'images' => 'array',
        'publish_to' => 'array',
    ];

    public function strategy(): BelongsTo
    {
        return $this->belongsTo(Strategy::class);
    }

    /**
     * The run turn that generated this idea; null for one the customer wrote.
     */
    public function runStep(): BelongsTo
    {
        return $this->belongsTo(AiRunStep::class, 'run_step_id');
    }

    /**
     * The post Genie produced from this idea, while its Mixpost draft is not trashed.
     */
    public function post(): HasOne
    {
        return $this->hasOne(GeniePost::class, 'idea_id')
            ->whereHas('mixpostPost')
            ->latestOfMany();
    }
}
