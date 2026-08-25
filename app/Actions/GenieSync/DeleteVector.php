<?php

namespace App\Actions\GenieSync;

use App\Abstracts\GenieData;
use App\Contracts\GenieSyncContract;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Stores;
use Throwable;

class DeleteVector implements GenieSyncContract
{
    public function handle(GenieData $data): ?GenieData
    {
        $providerId = $data->getModelProviderId();

        try {
            if (filled($providerId)) {
                Stores::delete($providerId);
            }

            $data->setResponse(['id' => $providerId, 'deleted' => true]);

            return $data;
        } catch (Throwable $exception) {
            Log::error('Genie vector store delete failed', [
                'provider_id' => $providerId,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
