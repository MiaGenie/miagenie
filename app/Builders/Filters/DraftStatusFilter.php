<?php

namespace App\Builders\Filters;

use App\Enums\DraftStatus;
use Illuminate\Contracts\Database\Eloquent\Builder;
use App\Contracts\Filter;

class DraftStatusFilter implements Filter
{
    public static function apply(Builder $builder, $value): Builder
    {
        if (DraftStatus::tryFrom($value)) {
            return $builder->where('status', $value);
        }

        return $builder;
    }
}
