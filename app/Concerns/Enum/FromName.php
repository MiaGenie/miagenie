<?php

namespace App\Concerns\Enum;

use Illuminate\Support\Str;

trait FromName
{
    /**
     * @param string $name
     * @return mixed
     */
    public static function fromName(string $name): mixed
    {
        $name = Str::upper($name);
        return constant("self::$name");
    }
}

