<?php

namespace App\Actions;

use App\Abstracts\GenieData;
use App\Contracts\GenieOutputContract;
use App\Enums\GenieSyncAction;
use Illuminate\Support\Facades\Log;

class GenieOutput implements GenieOutputContract
{
    /**
     * @param GenieData $data
     */
    public function handle(GenieData $data): void
    {
        $model = $data->getModel();

        try {
            $providerIdField = $data->getProviderIdField();
            $providerId = match ($data->getAction()) {
                GenieSyncAction::CREATE => $data->getResponseProviderId(),
                GenieSyncAction::DELETE => null,
            };
            $model->update([$providerIdField => $providerId]);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

}
