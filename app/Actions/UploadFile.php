<?php

namespace App\Actions;

use App\Enums\FileStatus;
use App\Models\File;
use App\Support\Facades\OpenAI;
use Illuminate\Support\Facades\Log;


class UploadFile
{

    /**
     * @param File $file
     * @return bool
     */
    public function __invoke(File $file): bool
    {
        $fileDb = File::find($file->id);
        $fileDb->status = FileStatus::UPLOADING;
        $fileDb->save();

        try {
            $upload = OpenAI::files()->upload(
                [
                    'file' => fopen($file->getFullPath(), 'r'),
                    'purpose' => 'assistants'
                ]
            );

            if ($upload->id && 'file' === $upload->object) {
                $fileDb->file_id = $upload->id;
                $fileDb->status = FileStatus::UPLOADED;
                $fileDb->save();
                return true;
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());

            $fileDb->status = FileStatus::PENDING;
            $fileDb->save();
        }

        return false;
    }
}
