<?php

namespace App\Builders;

use App\Builders\Filters\DraftStatusFilter;
use App\Contracts\Query;
use App\Models\Draft;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inovector\Mixpost\Facades\WorkspaceManager;

class DraftQuery implements Query
{
    public static function apply(Request $request): Builder
    {
        $query = Draft::query();

        $query = DraftStatusFilter::apply($query, $request->get('status'));

        return $query;
    }
}
