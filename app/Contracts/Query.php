<?php

namespace App\Contracts;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

interface Query
{
    /**
     * @param Request $request
     * @return Builder
     */
    public static function apply(Request $request): Builder;
}
