<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'content' => 'array'
    ];

    /**
     * @return HasOne
     */
    public function runBriefing(): HasOne
    {
        return $this->HasOne(RunBriefing::class)->latest();
    }
}
