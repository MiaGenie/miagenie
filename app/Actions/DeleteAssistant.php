<?php

namespace App\Actions;

use App\Models\Assistant;
use App\Support\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class DeleteAssistant
{

    /**
     * @param Assistant $assistant
     * @return bool
     */
    public function __invoke(Assistant $assistant): bool
    {
        try {
            $upload = OpenAI::assistants()->delete($assistant->assistant_provider_id);

            if ($upload->deleted) {
                return true;
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }

        return false;
    }
}
