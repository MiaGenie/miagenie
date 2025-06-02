<?php

namespace App\Builders\Filters;

use Illuminate\Contracts\Database\Eloquent\Builder;
use App\Contracts\Filter;

class ThreadTypesFilter implements Filter
{
    public static function apply(Builder $builder, $value): Builder
    {
        return $builder->whereIn('rule_id', $value);
    }
}
