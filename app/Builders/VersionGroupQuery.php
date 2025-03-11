<?php

namespace App\Builders;

use App\Builders\Filters\VersionGroupTypesFilter;
use App\Models\Version;
use App\Models\VersionField;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Contracts\Query;

class VersionGroupQuery implements Query
{
    /**
     * @param Request $request
     * @return Builder
     */
    public static function apply(Request $request): Builder
    {
        $query = VersionField::query();
        $version = Version::firstOrFailByUuid($request->route('version'));

        if ($request->has('group_type') && $request->get('group_type') !== null) {

            $query = VersionGroupTypesFilter::apply($query, $request->get('group_type'));

        }

        return $query->where('version_id', $version->id);
    }
}
