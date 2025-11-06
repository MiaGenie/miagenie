<?php

namespace App\Builders;

use App\Builders\Filters\IdeaContentPillarFilter;
use App\Builders\Filters\IdeaFunnelStageFilter;
use App\Builders\Filters\IdeaStatusFilter;
use App\Contracts\Query;
use App\Models\Idea;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class IdeaQuery implements Query
{
    public static function apply(Request $request): Builder
    {
        $query = Idea::query();

        if ($request->has('funnel_stage') && $request->get('funnel_stage') !== null) {
            $query = IdeaFunnelStageFilter::apply($query, $request->get('funnel_stage'));
        }
        if ($request->has('content_pillar') && $request->get('content_pillar') !== null) {
            $query = IdeaContentPillarFilter::apply($query, $request->get('content_pillar'));
        }

        $query = IdeaStatusFilter::apply($query, $request->get('status'));

        return $query;
    }
}
