<?php

namespace App\Actions;

use App\Enums\FileStatus;
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
        $vectorDb = Vector::find($vector->id);
        $vectorDb->status = FileStatus::UPLOADING;
        $vectorDb->save();

        $file_ids = array_column($vector->files, "file_id");
        try {
            $upload = OpenAI::vectorStores()->create(
                [
                    'file_ids' => $file_ids,
                    'name' => $vector->name,
                ]
            );

            if ($upload->id && 'vector_store' === $upload->object) {
                $vectorDb->vector_id = $upload->id;
                $vectorDb->status = FileStatus::UPLOADED;
                $vectorDb->save();
                return true;
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());

            $vectorDb->status = FileStatus::PENDING;
            $vectorDb->save();
        }

        return false;
    }
}
