<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Inovector\Mixpost\Concerns\Model\HasUuid;
use Inovector\Mixpost\Concerns\OwnedByWorkspace;

class Competitor extends Model
{
    use HasUuid;
    use OwnedByWorkspace;
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'genie_competitors';

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
     * @return HasMany
     */
    public function runCompetitors(): HasMany
    {
        return $this->hasMany(RunCompetitor::class, 'competitor_id');
    }
}
