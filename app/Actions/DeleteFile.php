<?php

namespace App\Actions;

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
        try {
            $upload = OpenAI::files()->delete($file->file_provider_id);

            if ($upload->deleted) {
                return true;
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }

        return false;
    }
}
