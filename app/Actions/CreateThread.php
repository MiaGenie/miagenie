<?php

namespace App\Actions;

use App\Abstracts\GenieData;
use App\Contracts\ThreadAction;
use App\Support\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class CreateThread implements ThreadAction
{

    /**
     * @param GenieData $data
     * @return ?GenieData
     */
    public function handle(GenieData $data): ?GenieData
    {
        try {
            $response = OpenAI::threads()->create([]);

            $data->setResponse($response->toArray());
            return $data;

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return null;
        }
    }

}
