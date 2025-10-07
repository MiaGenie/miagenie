<?php

namespace App\Builders;

use App\Builders\Filters\RuleTypesFilter;
use App\Contracts\Query;
use App\Models\Rule;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class RuleQuery implements Query
{
    public static function apply(Request $request): Builder
    {
        $query = Rule::query();

        if ($request->has('rule_type') && $request->get('rule_type') !== null) {
            $query = RuleTypesFilter::apply($query, $request->get('rule_type'));
        }

        return $query;
    }
}
