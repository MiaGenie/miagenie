<?php

namespace App\Actions\GenieSync;

use App\Abstracts\GenieData;
use App\Contracts\GenieSyncContract;
use App\Support\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class UploadVector implements GenieSyncContract
{

    /**
     * @param GenieData $data
     * @return ?GenieData
     */
    public function handle(GenieData $data): ?GenieData
    {
        try {
            $response = OpenAI::vectorStores()->create(
                $data->getData()
            );

            $data->setResponse($response->toArray());
            return $data;

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return null;
        }
    }
}
