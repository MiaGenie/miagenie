<?php

namespace App\Builders;

use App\Builders\Filters\AssistantTypesFilter;
use App\Contracts\Query;
use App\Models\Assistant;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AssistantQuery implements Query
{
    public static function apply(Request $request): Builder
    {
        $query = Assistant::query();

        if ($request->has('assistant_type') && $request->get('assistant_type') !== null) {
            $query = AssistantTypesFilter::apply($query, $request->get('assistant_type'));
        }

        return $query;
    }
}
