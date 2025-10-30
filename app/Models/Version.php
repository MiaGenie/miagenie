<?php

namespace App\Models;

use App\Enums\VersionGroupType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Validation\Rule;
use Inovector\Mixpost\Concerns\Model\HasUuid;
use App\Enums\VersionStatus;

class Version extends Model
{
    use HasUuid;

    /**
     * @var string
     */
    protected $table = 'genie_versions';

    /**
     * @var string[]
     */
    protected $fillable = [
        'uuid',
        'name',
        'description',
        'status',
        'is_default',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'status' => VersionStatus::class
    ];

    /**
     * @return HasMany
     */
    public function fields(): HasMany
    {
        return $this->hasMany(VersionField::class, 'version_id');
    }

    /**
     * @return HasMany
     */
    public function rules(): HasMany
    {
        return $this->hasMany(Rule::class);
    }

    /**
     * @return HasManyThrough
     */
    public function options(): HasManyThrough
    {
        return $this->through('fields')->has('options');
    }

    /**
     * @return HasMany
     */
    public function competitors(): HasMany
    {
        return $this->hasMany(VersionField::class, 'version_id')
            ->where('group_type', VersionGroupType::COMPETITORS)
            ->oldest('position');
    }

    /**
     * @return HasMany
     */
    public function briefings(): HasMany
    {
        return $this->hasMany(VersionField::class, 'version_id')
            ->where('group_type', VersionGroupType::BRIEFINGS)
            ->oldest('position');
    }

    /**
     * @return HasMany
     */
    public function strategies(): HasMany
    {
        return $this->hasMany(VersionField::class, 'version_id')
            ->where('group_type', VersionGroupType::STRATEGIES)
            ->oldest('position');
    }
}
