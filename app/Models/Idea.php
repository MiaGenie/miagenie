<?php

namespace App\Models;

use App\Concerns\Controller\GenieFields;
use App\Enums\FunnelStage;
use App\Enums\IdeaSource;
use App\Enums\IdeaStatus;
use App\Enums\PostFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Inovector\Mixpost\Concerns\Model\HasUuid;
use Inovector\Mixpost\Concerns\OwnedByWorkspace;

/**
 * One content idea, generated for a post format or written by hand.
 *
 * Everything a post is later produced from lives here: the objective, the suggested content, the
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
        'run_response_id',
        'theme',
        'objective',
        'suggested_content',
        'multimedia_suggestion',
        'production_instructions',
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
        'theme',
        'objective',
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

    public function drafts(): HasMany
    {
        return $this->HasMany(Draft::class);
    }

    public function runResponse(): BelongsTo
    {
        return $this->belongsTo(RunResponse::class);
    }
}
