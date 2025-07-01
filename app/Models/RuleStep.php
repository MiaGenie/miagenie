<?php

namespace App\Models;

use App\Enums\RuleSubType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'requires_review',
        'optional',
        'position'
    ];

    protected $casts = [
        'rule_sub_type' => RuleSubType::class,
    ];

    /**
     * @return BelongsTo
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(Rule::class, 'rule_id');
    }

    /**
     * @return HasMany
     */
    public function competitors(): HasMany
    {
        return $this->hasMany(RunResponse::class, 'step_id');
    }

    /**
     * @return BelongsTo
     */
    public function assistant(): BelongsTo
    {
        return $this->belongsTo(Assistant::class, 'assistant_id');
    }
}
