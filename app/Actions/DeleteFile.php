<?php

namespace App\Actions;

use App\Enums\FileStatus;
use App\Models\File;
use App\Support\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class DeleteFile
{

    /**
     * @param File $file
     * @return bool
     */
    public function __invoke(File $file): bool
    {

        $fileDb = File::find($file->id);
        $fileDb->status = FileStatus::DELETING;
        $fileDb->save();

        try {
            $upload = OpenAI::files()->delete($file->file_id);

            if ($upload->deleted) {
                return true;
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());

            $fileDb->status = FileStatus::PENDING_DELETION;
            $fileDb->save();
        }

        return false;
    }
}
