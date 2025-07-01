<?php

namespace App\Actions;

use App\Abstracts\GenieData;
use App\Support\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class StatusThread
{

    /**
     * @param GenieData $data
     * @return ?GenieData
     */
    public function handle(GenieData $data): ?GenieData
    {
        try {
            $response = OpenAI::threads()->runs()->retrieve(
                $data->getModelProviderId(),
                $data->lastRun()->run_provider_id,
            );

            $data->setResponse($response->toArray());
            return $data;

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return null;
        }
    }

}
