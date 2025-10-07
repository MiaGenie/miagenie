<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Inovector\Mixpost\Concerns\Model\HasUuid;
use Inovector\Mixpost\Concerns\OwnedByWorkspace;

class Strategy extends Model
{
    use HasUuid;
    use OwnedByWorkspace;

    protected $fillable = [
        'run_id',
        'workspace_id',
        'content'
    ];

    /**
     * @var string
     */
    protected $table = 'genie_strategies';

    /**
     * @var string[]
     */
    protected $guarded = [
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'content' => 'array'
    ];

    /**
     * @return BelongsTo
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(Run::class);
    }
}
