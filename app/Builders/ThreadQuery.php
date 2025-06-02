<?php

namespace App\Builders;

use App\Builders\Filters\ThreadTypesFilter;
use App\Contracts\Query;
use App\Models\Rule;
use App\Models\Thread;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ThreadQuery implements Query
{
    public static function apply(Request $request): Builder
    {
        $query = Thread::query();

        if ($request->has('rule_type') && $request->get('rule_type') !== null) {
            $rules = Rule::all()->where('rule_type', '=', $request->get('rule_type'));
            $query = ThreadTypesFilter::apply($query, $rules->pluck('id'));
        }

        return $query;
    }
}
