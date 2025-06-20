<?php

namespace App\Actions;

use App\Enums\OpenAISyncStatus;
use App\Models\File;
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
        $fileIds = array_column($vector->files, "file_id");

        /* TESTE */

        /*$filePaths = array_column($vector->files, "path");

        $fileIds = [];

        foreach ($vector->files as $vectorFile => $key) {
            $file = File::where('path', $vectorFile['path'])->get()->firstOrFail();
            $vector->files['file_id'] = $file->file_provider_id;
        }*/

        /* FIM TESTE */

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
                $vectorDb->status = OpenAISyncStatus::UPLOADED;
                $vectorDb->save();
                return true;
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }

        return false;
    }
}
