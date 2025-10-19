<?php

namespace App\Models;

use App\Concerns\Models\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Inovector\Mixpost\Concerns\Model\HasUuid;

class VersionFieldOption extends Model
{
    use HasUuid;
    use HasTranslations;

    /**
     * @var string
     */
    protected $table = 'genie_version_field_options';

    /**
     * @var string[]
     */
    protected $fillable = [
        'uuid',
        'field_id',
        'name',
        'code_name',
        'checked',
        'group',
        'position',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'name' => 'array'
    ];

    /**
     * @var array|string[]
     */
    public array $translatable = ['name'];

    /**
     * @return BelongsTo
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(VersionField::class, 'id');
    }
}
