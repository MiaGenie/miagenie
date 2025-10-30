<?php

namespace App\Builders\Filters;

use App\Enums\FunnelStage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use App\Contracts\Filter;

class IdeaFunnelStageFilter implements Filter
{
    public static function apply(Builder $builder, $value): Builder
    {
        if (FunnelStage::tryFrom($value)) {
            return $builder->where('funnel_stage', $value);
        }

        return $builder;
    }
}
