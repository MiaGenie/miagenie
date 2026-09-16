<?php

namespace App\Models;

use App\Enums\VersionGroupType;
use App\Enums\VersionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Inovector\Mixpost\Concerns\Model\HasUuid;

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
        'status' => VersionStatus::class,
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(VersionField::class, 'version_id');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(Rule::class, 'version_id');
    }

    public function options(): HasManyThrough
    {
        return $this->through('fields')->has('options');
    }

    public function briefings(): HasMany
    {
        return $this->hasMany(VersionField::class, 'version_id')
            ->where('group_type', VersionGroupType::BRIEFINGS)
            ->oldest('position');
    }

    public function strategies(): HasMany
    {
        return $this->hasMany(VersionField::class, 'version_id')
            ->where('group_type', VersionGroupType::STRATEGIES)
            ->oldest('position');
    }
}
