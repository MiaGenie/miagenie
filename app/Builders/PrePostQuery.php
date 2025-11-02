<?php

namespace App\Builders;

use App\Builders\Filters\PrePostStatusFilter;
use App\Contracts\Query;
use App\Models\Draft;
use App\Models\PrePost;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PrePostQuery implements Query
{
    public static function apply(Request $request): Builder
    {
        $query = PrePost::query();

        if ($request->has('status') && $request->get('status') !== null) {
            $query = PrePostStatusFilter::apply($query, $request->get('status'));
        }

        return $query;
    }
}
