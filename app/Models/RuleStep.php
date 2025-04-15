<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Inovector\Mixpost\Concerns\Model\HasUuid;

class RuleStep extends Model
{
    use HasUuid;

    public $table = 'genie_rule_steps';

    protected $fillable = [
        'uuid',
        'rule_id',
        'name',
        'description',
        'assistant_id',
        'message',
        'output',
        'position'
    ];
}
