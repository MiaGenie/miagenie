<?php

namespace App\Actions\GenieRun;

use App\Abstracts\GenieData;
use App\Contracts\GenieSyncContract;
use App\Support\Facades\OpenAI;
use Illuminate\Support\Facades\Log;


class CreateResponse implements GenieSyncContract
{

    /**
     * @param GenieData $data
     * @return ?GenieData
     */
    public function handle(GenieData $data): ?GenieData
    {
        try {
            $startTime = time();
            $response = OpenAI::responses()->create(
                $data->getRequest()
            );
            $endTime = time();

            $data->setDuration($endTime - $startTime);

            $data->setResponse($response->toArray());
            $data->setResponseStatus();
            return $data;

        } catch (\Exception $exception) {
            $errorMsg = $exception->getMessage();
            Log::error($errorMsg);
            $data->setError(true);
            $data->setResponse(
                ['Exception Error' => $errorMsg]
            );
            return $data;
        }
    }

}
