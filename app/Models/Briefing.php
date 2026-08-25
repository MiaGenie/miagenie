<?php

namespace App\Models;

use App\Enums\BriefingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Inovector\Mixpost\Concerns\Model\HasUuid;
use Inovector\Mixpost\Concerns\OwnedByWorkspace;

class Briefing extends Model
{
    use HasUuid;
    use OwnedByWorkspace;
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'genie_briefings';

    /**
     * @var string[]
     */
    protected $fillable = [
        'content',
        'version_id',
        'status',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'content' => 'array',
        'status' => BriefingStatus::class,
    ];

    public function runBriefing(): HasOne
    {
        return $this->HasOne(RunBriefing::class)->latest();
    }
}
