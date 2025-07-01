<?php

namespace App\Builders\Filters;

use Illuminate\Contracts\Database\Eloquent\Builder;
use App\Contracts\Filter;

class ThreadIdFilter implements Filter
{
    public static function apply(Builder $builder, $value): Builder
    {
        return $builder->where('thread_id', $value);
    }
}
