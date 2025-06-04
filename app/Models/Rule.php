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
    public function threads(): HasMany
    {
        return $this->hasMany(Thread::class, 'rule_id')->oldest('id');
    }
}
