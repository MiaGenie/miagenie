<?php

namespace App\Actions\GenieState;

use App\Abstracts\GenieData;
use App\Contracts\GenieStateContract;
use App\Enums\RunStatus;
use Illuminate\Support\Facades\Log;

class GenieStateRunResponses implements GenieStateContract
{
    /**
     * @param GenieData $data
     * @param string $state
     */
    public function handle(GenieData $data, string $state): void
    {
        $model = $data->getModel();
        try {
            switch ($state) {
                default:
                case 'run':
                    $status = RunStatus::RUNNING;
                    break;
                case 'end':
                    $status = $model->step->requires_review ? RunStatus::PENDING_REVIEW : RunStatus::COMPLETE;
                    break;
                case 'fail':
                    $status = RunStatus::ERROR;
                    break;
            }

            $model->update(['status' => $status]);
            $model->run->update(['status' => $status]);

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }
}
