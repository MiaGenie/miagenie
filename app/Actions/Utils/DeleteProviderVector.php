<?php

namespace App\Actions\Utils;

use App\Support\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class DeleteProviderVector
{

    /**
     * @param string $vector
     * @return bool
     */
    public function __invoke(string $vector): bool
    {
        try {
            $upload = OpenAI::vectorStores()->delete($vector);

            if ($upload->deleted) {
                return true;
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }

        return false;
    }
}
