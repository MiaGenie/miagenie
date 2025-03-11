<?php

namespace App\Contracts;

use Illuminate\Contracts\Database\Eloquent\Builder;

interface Filter
{
    /**
     * @param Builder $builder
     * @param $value
     * @return Builder
     */
    public static function apply(Builder $builder, $value): Builder;
}
