<?php

namespace App\Concerns\Enum;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait HasState
{
    /**
     * @return bool
     */
    abstract public function hasError(): bool;

    /**
     * @return bool
     */
    abstract public function requiresUpdate(): bool;

    /**
     * @return bool
     */
    abstract public function isComplete(): bool;
}

