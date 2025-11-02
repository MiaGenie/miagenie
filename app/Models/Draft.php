<?php

namespace App\Models;

use App\Concerns\Controller\GenieFields;
use App\Enums\DraftStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Inovector\Mixpost\Concerns\Model\HasUuid;
use Inovector\Mixpost\Concerns\OwnedByWorkspace;

class Draft extends Model
{
    use HasUuid;
    use OwnedByWorkspace;
    use GenieFields;

    /**
     * @var string
     */
    protected $table = 'genie_drafts';

    protected $fillable = [
        'workspace_id',
        'idea_id',
        'topic',
        'goal',
        'key_ideas',
        'media',
        'status'
    ];

    /**
     * @var string[]
     */
    protected array $genie_fields = [
        'topic',
        'goal',
        'key_ideas',
        'media',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'status' => DraftStatus::class,
    ];

    /**
     * @return BelongsTo
     */
    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    /**
     * @return HasMany
     */
    public function prePosts(): HasMany
    {
        return $this->HasMany(PrePost::class);
    }

    /**
     * @return BelongsTo
     */
    public function runResponse(): BelongsTo
    {
        return $this->belongsTo(RunResponse::class);
    }
}