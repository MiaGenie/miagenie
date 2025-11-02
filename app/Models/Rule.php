<?php

namespace App\Models;

use App\Enums\RuleType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'link_upstream',
        'name',
        'description',
        'status',
    ];

    protected $casts = [
        'rule_type' => RuleType::class,
    ];


    protected function linkUpstream(): Attribute
    {
        return Attribute::make(

            set: fn (?bool $value) => boolval($value),

        );
    }

    /**
     * @return BelongsTo
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(Version::class);
    }


    /**
     * @return HasMany
     */
    public function ruleSteps(): HasMany
    {
        return $this->hasMany(RuleStep::class, 'rule_id')->orderBy('position');
    }

    /**
     * @return HasMany
     */
    public function runs(): HasMany
    {
        return $this->hasMany(Run::class, 'rule_id');
    }
}
