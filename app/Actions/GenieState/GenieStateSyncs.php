<?php

namespace App\Actions\GenieState;

use App\Abstracts\GenieData;
use App\Contracts\GenieStateContract;
use App\Enums\GenieSyncAction;
use App\Enums\GenieSyncStatus;
use Illuminate\Support\Facades\Log;

class GenieStateSyncs implements GenieStateContract
{
    /**
     * @param GenieData $data
     * @param string $state
     */
    public function handle(GenieData $data, string $state): void
    {
        try {

            $action = $data->getAction();
            switch ($action) {
                case GenieSyncAction::CREATE:
                    $status = match ($state) {
                        'init' => GenieSyncStatus::CREATING,
                        'end' => GenieSyncStatus::CREATED,
                        'fail' => GenieSyncStatus::FAILED_CREATION
                    };
                    break;
                case GenieSyncAction::UPDATE:
                    $status = match ($state) {
                        'init' => GenieSyncStatus::UPDATING,
                        'end' => GenieSyncStatus::UPDATED,
                        'fail' => GenieSyncStatus::FAILED_UPDATE
                    };
                    break;
                case GenieSyncAction::DELETE:
                    $status = match ($state) {
                        'init' => GenieSyncStatus::DELETING,
                        'end' => GenieSyncStatus::DELETED,
                        'fail' => GenieSyncStatus::FAILED_DELETION
                    };
                    break;
            }

            $model = $data->getModel();
            $model->update(['status' => $status]);

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }
}
