<?php

namespace App\Genie\Data;

use App\Contracts\GenieDataContract;
use App\Models\Thread;

class Ideas implements GenieDataContract
{
    /**
     * @return array
     */
    public function get(Thread $thread): array
    {
        return [];
    }
}
