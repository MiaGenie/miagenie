<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Inovector\Mixpost\Concerns\Model\HasUuid;

class Vector extends Model
{

    use HasUuid;

    /**
     * @var string
     */
    public $table = 'genie_vectors';

    /**
     * @var array
     */
    protected $fillable = [
        'uuid',
        'name',
        'description',
        'files',
        'vector_type',
        'vector_id',
        'status',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'files' => 'object',
    ];

    /**
     * @param Builder $query
     * @param File $file
     * @return Builder
     */
    public function scopeHasFiles(Builder $query, File $file): Builder
    {
        return $query->whereRaw("JSON_SEARCH(files, 'all', ?, NULL, '$[*].id') is not null", [(string)$file->id]);
    }
}
