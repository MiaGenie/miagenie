<?php

namespace App\Models;

use App\Enums\RuleSubType;
use App\Enums\RuleType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Inovector\Mixpost\Concerns\Model\HasUuid;

class Rule extends Model
{
    use HasUuid;

    public $table = 'genie_rules';

    protected $fillable = [
        'uuid',
        'version_id',
        'rule_type',
        'name',
        'description',
        'status',
        'position',
    ];

    protected $casts = [
        'rule_type' => RuleType::class,
    ];


    /**
     * @return HasMany
     */
    public function ruleSteps(): HasMany
    {
        return $this->hasMany(RuleStep::class, 'rule_id');
    }

    /**
     * @return HasMany
     */
    public function runs(): HasMany
    {
        return $this->hasMany(Run::class, 'rule_id');
    }
}
