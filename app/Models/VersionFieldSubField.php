<?php

namespace App\Models;

use App\Concerns\Models\HasTranslations;
use App\Enums\SubFieldType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Inovector\Mixpost\Concerns\Model\HasUuid;

class VersionFieldSubField extends Model
{
    use HasTranslations;
    use HasUuid;

    /**
     * @var string
     */
    protected $table = 'genie_version_field_sub_fields';

    /**
     * @var string[]
     */
    protected $fillable = [
        'uuid',
        'field_id',
        'parent_id',
        'name',
        'sub_code_name',
        'description',
        'type',
        'min_length',
        'max_length',
        'pattern',
        'min_items',
        'max_items',
        'required',
        'enum_values',
        'icon',
        'class',
        'block',
        'editable',
        'position',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'type' => SubFieldType::class,
        'name' => 'array',
        'description' => 'array',
        'enum_values' => 'array',
        'required' => 'boolean',
        'editable' => 'boolean',
    ];

    /**
     * @var array|string[]
     */
    public array $translatable = [
        'name',
        'description',
    ];

    /**
     * Whether a sub-field of this shape may be edited before the strategy is approved.
     *
     * A value the user can retype is editable; a structure is not. Objects and arrays of
     * objects are shapes rather than values, and an enum is a choice the model made from a
     * fixed list, so freehand editing would put the strategy outside its own schema.
     */
    public static function allowsEditing(SubFieldType $type, bool $hasChildren, bool $hasEnumValues): bool
    {
        return match ($type) {
            SubFieldType::OBJECT => false,
            SubFieldType::ARRAY => ! $hasChildren && ! $hasEnumValues,
            SubFieldType::STRING => ! $hasEnumValues,
            SubFieldType::BOOLEAN => true,
        };
    }

    /**
     * Whether this sub-field may be edited, judged from its own children and enum values.
     */
    public function supportsEditing(): bool
    {
        $hasChildren = $this->relationLoaded('children')
            ? $this->children->isNotEmpty()
            : $this->children()->exists();

        return self::allowsEditing($this->type, $hasChildren, filled($this->enum_values));
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(VersionField::class, 'field_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->oldest('position');
    }

    /**
     * The children of this sub-field with their own descendants eager loaded, so a tree of
     * any depth can be fetched in one pass.
     */
    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }
}
