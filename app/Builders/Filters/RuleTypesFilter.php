<?php

namespace App\Builders\Filters;

use Illuminate\Contracts\Database\Eloquent\Builder;
use App\Contracts\Filter;
use App\Enums\RuleType;

class RuleTypesFilter implements Filter
{
    public static function apply(Builder $builder, $value): Builder
    {
        if (RuleType::tryFrom($value)) {
            return $builder->where('rule_type', $value);
        }

        return $builder;
    }
}
