<?php

namespace App\Models;

use App\Enums\FormFieldType;
use App\Enums\FormInputType;
use App\Enums\VersionGroupType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Inovector\Mixpost\Concerns\Model\HasUuid;
use App\Concerns\Models\HasTranslations;

class VersionField extends Model
{
    use HasFactory;
    use HasUuid;
    use HasTranslations;

    /**
     * @var string
     */
    protected $table = 'genie_version_fields';

    /**
     * @var string[]
     */
    protected $fillable = [
        'uuid',
        'version_id',
        'group_type',
        'name',
        'code_name',
        'description',
        'sub_description',
        'field_type',
        'input_type',
        'min_length',
        'max_length',
        'min_value',
        'max_value',
        'step',
        'rows',
        'required',
        'genie_required',
        'is_identifier',
        'position',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'field_type' => FormFieldType::class,
        'input_type' => FormInputType::class,
        'group_type' => VersionGroupType::class,
        'name' => 'array',
        'description' => 'array',
        'sub_description' => 'array'
    ];

    /**
     * @var array|string[]
     */
    public array $translatable = ['name', 'description', 'sub_description'];

    /**
     * @return BelongsTo
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(Version::class, 'id');
    }

    /**
     * @return HasMany
     */
    public function options(): HasMany
    {
        return $this->hasMany(VersionFieldOption::class, 'field_id');
    }
}
