<?php

namespace App\Concerns;

use App\Abstracts\GenieData;
use App\Models\RunLog;

trait GenieLogger
{
    /**
     * @param string $type
     * @param string $action
     * @param GenieData $data
     * @return RunLog
     */
    protected function logRun(string $type, string $action, GenieData $data): RunLog
    {
        return RunLog::create([
            'type' => $type,
            'action' => $action,
            'data' => $data->request,
            'response' => $data->response,
        ]);

    }
}
