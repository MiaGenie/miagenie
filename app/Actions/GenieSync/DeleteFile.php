<?php

namespace App\Actions\GenieSync;

use App\Abstracts\GenieData;
use App\Contracts\GenieSyncContract;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Files;
use Throwable;

class DeleteFile implements GenieSyncContract
{
    public function handle(GenieData $data): ?GenieData
    {
        $providerId = $data->getModelProviderId();

        try {
            if (filled($providerId)) {
                Files::delete($providerId);
            }

            $data->setResponse(['id' => $providerId, 'deleted' => true]);

            return $data;
        } catch (Throwable $exception) {
            Log::error('Genie file delete failed', [
                'provider_id' => $providerId,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
