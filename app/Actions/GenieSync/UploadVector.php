<?php

namespace App\Actions\GenieSync;

use App\Abstracts\GenieData;
use App\Contracts\GenieSyncContract;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Stores;
use Throwable;

class UploadVector implements GenieSyncContract
{
    public function handle(GenieData $data): ?GenieData
    {
        $request = $data->getData();

        try {
            $store = Stores::create(
                name: $request['name'],
                fileIds: $request['file_ids'] ?? [],
            );

            $data->setResponse(['id' => $store->id, 'name' => $store->name]);

            return $data;
        } catch (Throwable $exception) {
            Log::error('Genie vector store creation failed', [
                'vector_id' => $data->getModel()->id,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
