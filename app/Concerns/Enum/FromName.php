<?php

namespace App\Concerns\Enum;

use Illuminate\Support\Collection;

trait FromName
{
    /**
     * @param string $name
     * @return mixed
     */
    public static function fromName(string $name): mixed
    {
        $name = strtoupper($name);
        return constant("self::$name");
    }
}

