<?php

namespace App\Models;

use App\Enums\RuleSubType;
use App\Enums\RuleType;
use Illuminate\Database\Eloquent\Model;
use Inovector\Mixpost\Concerns\Model\HasUuid;

class Rule extends Model
{
    use HasUuid;

    public $table = 'genie_rules';

    protected $fillable = [
        'uuid',
        'version_id',
        'rule_type',
        'rule_sub_type',
        'name',
        'description',
        'status',
        'position',
    ];

    protected $casts = [
        'rule_type' => RuleType::class,
        'rule_sub_type' => RuleSubType::class,
    ];
}
