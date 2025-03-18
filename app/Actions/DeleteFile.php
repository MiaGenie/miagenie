<?php

namespace App\Actions;

use App\Models\File;
use App\Support\Facades\OpenAI;


class DeleteFile
{

    /**
     * @param File $file
     * @return bool
     */
    public function __invoke(File $file): bool
    {
        $upload = OpenAI::files()->delete($file->file_id);

        return $upload->deleted ?? false;
    }
}
