<?php

namespace App\Actions\Utils;

use App\Models\Assistant;
use App\Support\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class DeleteProviderAssistant
{

    /**
     * @param string $assistant
     * @return bool
     */
    public function __invoke(string $assistant): bool
    {
        try {
            $upload = OpenAI::assistants()->delete($assistant);

            if ($upload->deleted) {
                return true;
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }

        return false;
    }
}
