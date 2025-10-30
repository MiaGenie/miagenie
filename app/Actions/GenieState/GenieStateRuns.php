<?php

namespace App\Actions\GenieState;

use App\Contracts\GenieRunDataContract;
use App\Enums\RunStatus;
use App\Genie\Data\GenieRunData;
use Illuminate\Support\Facades\Log;

class GenieStateRuns
{
    /**
     * @param GenieRunData $data
     * @param string $state
     */
    public function handle(GenieRunDataContract $data, string $state): void
    {
        try {
            $status = match ($state) {
                'run' => RunStatus::RUNNING,
                'end' => RunStatus::COMPLETE,
                'fail' => RunStatus::ERROR
            };

            $model = $data->getModel();
            $model->update(['status' => $status]);

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }
}
