<?php

namespace App\Builders\Filters;

use App\Contracts\Filter;
use App\Enums\PostFormat;
use Illuminate\Contracts\Database\Eloquent\Builder;

class IdeaPostFormatFilter implements Filter
{
    public static function apply(Builder $builder, $value): Builder
    {
        if (PostFormat::tryFrom((int) $value)) {
            return $builder->where('post_format', $value);
        }

        return $builder;
    }
}
