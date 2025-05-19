<?php

namespace App\Models;

use App\Enums\RuleType;
use Illuminate\Database\Eloquent\Model;
use Inovector\Mixpost\Concerns\Model\HasUuid;

class Thread extends Model
{
    use HasUuid;

    public $table = 'genie_threads';

    protected $fillable = [
        'uuid',
        'workspace_id',
        'rule_type',
        'thread_provider_id'
    ];

    protected $casts = [
        'rule_type' => RuleType::class
    ];
}
