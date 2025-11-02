<?php

namespace App\Builders\Filters;

use App\Enums\PrePostStatus;
use Illuminate\Contracts\Database\Eloquent\Builder;
use App\Contracts\Filter;

class PrePostStatusFilter implements Filter
{
    public static function apply(Builder $builder, $value): Builder
    {
        if (PrePostStatus::tryFrom($value)) {
            return $builder->where('status', $value);
        }

        return $builder;
    }
}
