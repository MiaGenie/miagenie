<?php

namespace App\Actions;

use App\Enums\GenieSyncAction;
use App\Enums\GenieSyncStatus;
use Illuminate\Support\Facades\Log;

class GenieState
{
    /**
     * @param \App\Models\File|\App\Models\Vector|\App\Models\Assistant|\App\Models\Thread $model
     * @param GenieSyncAction $action
     * @param string $state
     */
    public function handle(
        \App\Models\File|\App\Models\Vector|\App\Models\Assistant|\App\Models\Thread $model,
        GenieSyncAction $action,
        string $state
    ): void {
        try {

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

            $model->update(['status' => $status]);

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }
}
