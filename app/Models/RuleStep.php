<?php

namespace App\Models;

use App\Concerns\Models\HasTranslations;
use App\Enums\RuleSubType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Inovector\Mixpost\Concerns\Model\HasUuid;

class RuleStep extends Model
{
    use HasTranslations;
    use HasUuid;

    protected $fillable = [
        'uuid',
        'rule_id',
        'rule_sub_type',
        'link_upstream',
        'name',
        'description',
        'instructions',
        'model_profile_id',
        'response_format',
        'message',
        'output',
        'requires_review',
        'review_message_user',
        'review_message_system',
        'optional',
        'position',
        'depends_on_field',
        'depends_on_option',
    ];

    public $table = 'genie_rule_steps';

    protected $casts = [
        'rule_sub_type' => RuleSubType::class,
        'output' => 'array',
    ];

    /**
     * @var array|string[]
     */
    public array $translatable = [
        'instructions',
        'message',
        'review_message_user',
        'review_message_system',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(Rule::class, 'rule_id');
    }

    public function modelProfile(): BelongsTo
    {
        return $this->belongsTo(ModelProfile::class, 'model_profile_id');
    }

    public function competitors(): HasMany
    {
        return $this->hasMany(RunResponse::class, 'step_id');
    }

    public function dependsOnField(): HasOne
    {
        return $this->hasOne(VersionField::class, 'id', 'depends_on_field');
    }

    public function dependsOnOption(): HasOne
    {
        return $this->hasOne(VersionFieldSubField::class, 'id', 'depends_on_option');
    }
}
