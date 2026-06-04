<?php

namespace App\Models;

use App\Concerns\Models\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Inovector\Mixpost\Concerns\Model\HasUuid;

class PlanInfo extends Model
{
    use HasUuid;
    use HasTranslations;

    /**
     * @var string
     */
    protected $table = 'genie_plans_info';

    /**
     * @var string[]
     */
    protected $fillable = [
        'uuid',
        'plan_id',
        'description'
    ];

    /**
     * @var array|string[]
     */
    public array $translatable = [
        'description'
    ];

}
