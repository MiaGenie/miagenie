<?php

namespace App\Actions;

use App\Models\Vector;
use App\Support\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class DeleteVector
{

    /**
     * @param Vector $vector
     * @return bool
     */
    public function __invoke(Vector $vector): bool
    {
        try {
            $upload = OpenAI::vectorStores()->delete($vector->vector_provider_id);

            if ($upload->deleted) {
                return true;
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }

        return false;
    }
}
