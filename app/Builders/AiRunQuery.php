<?php

namespace App\Builders;

use App\Builders\Filters\ThreadTypesFilter;
use App\Contracts\Query;
use App\Models\AiRun;
use App\Models\Rule;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AiRunQuery implements Query
{
    /**
     * The admin looks across the whole installation, so the workspace scope AiRun carries — which
     * would quietly narrow the list to whichever workspace the admin last opened — is lifted.
     */
    public static function apply(Request $request): Builder
    {
        $query = AiRun::withoutWorkspace();

        if ($request->has('rule_type') && $request->get('rule_type') !== null) {
            $rules = Rule::all()->where('rule_type', '=', $request->get('rule_type'));
            $query = ThreadTypesFilter::apply($query, $rules->pluck('id'));
        }

        return $query;
    }
}
