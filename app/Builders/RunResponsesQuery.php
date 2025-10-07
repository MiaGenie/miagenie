<?php

namespace App\Builders;

use App\Builders\Filters\RunIdFilter;
use App\Contracts\Query;
use App\Models\Run;
use App\Models\RunResponse;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class RunResponsesQuery implements Query
{

    public static function apply(Request $request): Builder
    {
        $query = RunResponse::query();
        $run = Run::firstOrFailByUuid($request->route('run'));
        $query = RunIdFilter::apply($query, $run->id);

        return $query;
    }
}
