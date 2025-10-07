<?php

namespace App\Contracts;

use App\Abstracts\GenieData;

interface GenieStateContract
{

    /**
     * @param GenieData $data
     * @param string $state
     */
    public function handle(GenieData $data, string $state): void;

}
