<?php

namespace App\Builders\Filters;

use Illuminate\Contracts\Database\Eloquent\Builder;
use App\Contracts\Filter;

class RunIdFilter implements Filter
{
    public static function apply(Builder $builder, $value): Builder
    {
        return $builder->where('run_id', $value);
    }
}
