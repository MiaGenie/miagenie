<?php

namespace App\Builders;

use App\Builders\Filters\ThreadIdFilter;
use App\Contracts\Query;
use App\Models\Thread;
use App\Models\ThreadRuns;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ThreadRunsQuery implements Query
{
    public static function apply(Request $request): Builder
    {
        $query = ThreadRuns::query();

        $thread = Thread::firstOrFailByUuid($request->route('thread'));

        $query = ThreadIdFilter::apply($query, $thread->id);

        return $query;
    }
}
