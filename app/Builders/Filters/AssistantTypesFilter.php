<?php

namespace App\Builders\Filters;

use Illuminate\Contracts\Database\Eloquent\Builder;
use App\Contracts\Filter;
use App\Enums\AssistantType;

class AssistantTypesFilter implements Filter
{
    public static function apply(Builder $builder, $value): Builder
    {
        if (AssistantType::tryFrom($value)) {
            return $builder->where('assistant_type', $value);
        }

        return $builder;
    }
}
