<?php

namespace App\Models;

use App\Enums\DraftStatus;
use Illuminate\Database\Eloquent\Model;
use Inovector\Mixpost\Concerns\Model\HasUuid;
use Inovector\Mixpost\Concerns\OwnedByWorkspace;

class Draft extends Model
{
    use HasUuid;
    use OwnedByWorkspace;

    protected $fillable = [
        'workspace_id',
        'idea_id',
        'goal',
        'caption',
        'media',
        'status'
    ];

    /**
     * @var string
     */
    protected $table = 'genie_drafts';

    /**
     * @var string[]
     */
    protected $casts = [
        'status' => DraftStatus::class,
    ];
}