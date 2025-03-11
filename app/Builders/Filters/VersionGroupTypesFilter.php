<?php

namespace App\Builders\Filters;

use App\Enums\VersionGroupType;
use Illuminate\Contracts\Database\Eloquent\Builder;
use App\Contracts\Filter;

class VersionGroupTypesFilter implements Filter
{
    /**
     * @param Builder $builder
     * @param $value
     * @return Builder
     */
    public static function apply(Builder $builder, $value): Builder
    {
        if (VersionGroupType::tryFrom($value)) {
            return $builder->where('group_type', $value);
        }

        return $builder;
    }
}
