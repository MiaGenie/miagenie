<?php

namespace App\Models;

use App\Enums\RuleSubType;
use Illuminate\Database\Eloquent\Model;
use Inovector\Mixpost\Concerns\Model\HasUuid;

class RuleStep extends Model
{
    use HasUuid;

    public $table = 'genie_rule_steps';

    protected $fillable = [
        'uuid',
        'rule_id',
        'rule_sub_type',
        'name',
        'description',
        'assistant_id',
        'message',
        'output',
        'optional',
        'position'
    ];

    protected $casts = [
        'rule_sub_type' => RuleSubType::class,
    ];
}
