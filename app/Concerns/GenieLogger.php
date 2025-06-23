<?php

namespace App\Concerns;

use App\Abstracts\GenieData;
use App\Enums\GenieSyncAction;
use App\Enums\GenieType;
use App\Models\Log;

trait GenieLogger
{
    /**
     * @param GenieType $type
     * @param GenieSyncAction $action
     * @param GenieData $data
     * @return Log
     */
    protected function logRun(GenieType $type, GenieSyncAction $action, GenieData $data): Log
    {
        return Log::create([
            'type' => $type,
            'action' => $action,
            'data' => $data->getRequest(),
            'response' => $data->getResponse(),
        ]);

    }
}
