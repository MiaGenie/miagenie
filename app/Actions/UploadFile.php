<?php

namespace App\Actions;

use App\Enums\FileStatus;
use App\Models\File;
use App\Support\Facades\OpenAI;


class UploadFile
{

    /**
     * @param File $file
     * @return bool
     */
    public function __invoke(File $file): bool
    {
        $File = File::find($file->id);
        $File->status = FileStatus::ENABLED;
        $File->save();

        $upload = OpenAI::files()->upload(
            [
                'file' => fopen($file->getFullPath(), 'r'),
                'purpose' => 'assistants'
            ]
        );

        if ($upload->id && 'file' === $upload->object) {
            $File = File::find($file->id);
            $File->file_id = $upload->id;
            $File->status = FileStatus::ENABLED;
            $File->save();
            return true;
        }

        return false;
    }
}
