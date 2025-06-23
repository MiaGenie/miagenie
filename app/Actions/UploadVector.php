<?php

namespace App\Actions;

use App\Enums\GenieSyncStatus;
use App\Models\Vector;
use App\Support\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class UploadVector
{

    /**
     * @param Vector $vector
     * @return bool
     */
    public function __invoke(Vector $vector): bool
    {
        $fileIds = array_column($vector->files, "file_provider_id");
        try {
            $upload = OpenAI::vectorStores()->create(
                [
                    'file_ids' => $fileIds,
                    'name' => $vector->name,
                ]
            );

            if ($upload->id && 'vector_store' === $upload->object) {
                $vectorDb = Vector::find($vector->id);
                $vectorDb->vector_provider_id = $upload->id;
                $vectorDb->status = GenieSyncStatus::CREATED;
                $vectorDb->save();
                return true;
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }

        return false;
    }
}
