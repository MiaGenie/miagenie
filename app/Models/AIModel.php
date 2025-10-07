<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Inovector\Mixpost\Concerns\Model\HasUuid;

class AIModel extends Model
{
    use HasUuid;

    /**
     * @var string
     */
    protected $table = 'genie_ai_models';

    /**
     * @var string[]
     */
    protected $fillable = [
        'uuid',
        'model',
        'json_schema',
        'temperature_top_p',
        'file_search',
        'reasoning_effort',
    ];

}
