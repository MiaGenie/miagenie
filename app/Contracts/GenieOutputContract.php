<?php

namespace App\Contracts;

use App\Abstracts\GenieData;

interface GenieOutputContract
{

    /**
     * @param GenieData $data
     */
    public function handle(GenieData $data): void;

}
