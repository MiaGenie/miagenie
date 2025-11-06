<?php

namespace App\Builders\Filters;

use App\Enums\IdeaStatus;
use Illuminate\Contracts\Database\Eloquent\Builder;
use App\Contracts\Filter;

class IdeaStatusFilter implements Filter
{
    public static function apply(Builder $builder, $value): Builder
    {
        if (IdeaStatus::tryFrom($value)) {
            $builder->where('status', $value);
        } else {
            $builder->whereNot('status', IdeaStatus::TRASH);
        }

        return $builder;
    }
}
