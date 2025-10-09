<?php

namespace App\Models;

use App\Enums\RuleSubType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'instructions',
        'ai_model',
        'response_format',
        'json_schema',
        'temperature',
        'top_p',
        'reasoning_effort',
        'vector_id',
        'message',
        'output',
        'requires_review',
        'review_message_user',
        'review_message_system',
        'optional',
        'position',
        'depends_on_field',
        'depends_on_option'
    ];

    protected $casts = [
        'rule_sub_type' => RuleSubType::class,
        'output' => 'array',
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
     * @return HasOne
     */
    public function dependsOnField(): HasOne
    {
        return $this->hasOne(VersionField::class, 'id', 'depends_on_field');
    }

    public function dependsOnOption(): HasOne
    {
        return $this->hasOne(VersionFieldOption::class, 'id', 'depends_on_option');
    }
}
