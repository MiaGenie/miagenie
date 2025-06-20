<?php

namespace App\Actions\Utils;

use App\Support\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class DeleteProviderFile
{

    /**
     * @param string $file
     * @return bool
     */
    public function __invoke(string $file): bool
    {
        try {
            $upload = OpenAI::files()->delete($file);

            if ($upload->deleted) {
                return true;
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }

        return false;
    }
}
