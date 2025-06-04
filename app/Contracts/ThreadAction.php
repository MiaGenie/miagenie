<?php

namespace App\Contracts;

use App\Abstracts\GenieData;
use App\Models\Thread;
use OpenAI\Responses\Threads\ThreadResponse;

interface ThreadAction
{

    /**
     * @param GenieData $data
     * @return GenieData
     */
    public function handle(GenieData $data): ?GenieData;

}
