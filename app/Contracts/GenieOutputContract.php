<?php

namespace App\Contracts;

use App\Abstracts\GenieData;
use OpenAI\Responses\Threads\ThreadResponse;

interface GenieOutputContract
{

    /**
     * @param GenieData $data
     * @return GenieData
     */
    public function handle(GenieData $data);

}
