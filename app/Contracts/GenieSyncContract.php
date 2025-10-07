<?php

namespace App\Contracts;

use App\Abstracts\GenieData;

interface GenieSyncContract
{

    /**
     * @param GenieData $data
     * @return ?GenieData
     */
    public function handle(GenieData $data): ?GenieData;

}
