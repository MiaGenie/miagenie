<?php

namespace App\Builders\Filters;

use App\Enums\FunnelStage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use App\Contracts\Filter;

class IdeaContentPillarFilter implements Filter
{
    public static function apply(Builder $builder, $value): Builder
    {

        return $builder->where('content_pillar', $value);

    }
}
