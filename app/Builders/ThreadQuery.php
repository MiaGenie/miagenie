<?php

namespace App\Builders;

use App\Builders\Filters\RuleTypesFilter;
use App\Contracts\Query;
use App\Models\Thread;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ThreadQuery implements Query
{
    public static function apply(Request $request): Builder
    {
        $query = Thread::query();

        // $rules = Rule::firstOrFailByUuid($query);

        if ($request->has('rule_type') && $request->get('rule_type') !== null) {
            $query = RuleTypesFilter::apply($query, $request->get('rule_type'));
        }

        return $query;
    }
}
