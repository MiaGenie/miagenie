<?php

namespace App\Actions\GenieRun;

use App\Abstracts\GenieData;
use App\Contracts\GenieSyncContract;
use App\Support\Facades\OpenAI;
use Illuminate\Support\Facades\Log;


class RetrieveResponse implements GenieSyncContract
{

    /**
     * @param GenieData $data
     * @return ?GenieData
     */
    public function handle(GenieData $data): ?GenieData
    {
        try {
            $response = OpenAI::responses()->retrieve(
                $data->getModelProviderId()
            );

            $data->setResponse($response->toArray());
            return $data;

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return null;
        }
    }

}
