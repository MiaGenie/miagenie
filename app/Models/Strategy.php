<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Inovector\Mixpost\Concerns\Model\HasUuid;
use Inovector\Mixpost\Concerns\OwnedByWorkspace;

class Strategy extends Model
{
    use HasUuid;
    use OwnedByWorkspace;

    /**
     * @var string
     */
    protected $table = 'genie_strategies';

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
}
