<?php

namespace App\Models;

use App\Concerns\Controller\GenieFields;
use App\Enums\DraftStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Inovector\Mixpost\Concerns\Model\HasUuid;
use Inovector\Mixpost\Concerns\OwnedByWorkspace;
use Inovector\Mixpost\Models\Post;

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
     * @return HasOne
     */
    public function prePost(): HasOne
    {
        return $this->HasOne(PrePost::class);
    }

    /**
     * @return HasOneThrough
     */
    public function draftPost(): HasOneThrough
    {
        return $this->HasOneThrough(Post::class, PrePost::class, 'draft_id', 'id', 'id', 'post_id');
    }

    /**
     * @return BelongsTo
     */
    public function runResponse(): BelongsTo
    {
        return $this->belongsTo(RunResponse::class);
    }
}