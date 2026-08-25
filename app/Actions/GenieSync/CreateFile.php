<?php

namespace App\Actions\GenieSync;

use App\Abstracts\GenieData;
use App\Contracts\GenieSyncContract;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Files;
use Throwable;

class CreateFile implements GenieSyncContract
{
    public function handle(GenieData $data): ?GenieData
    {
        try {
            $stored = Files::putFromPath($data->getModel()->getFullPath());

            $data->setResponse(['id' => $stored->id]);

            return $data;
        } catch (Throwable $exception) {
            Log::error('Genie file upload failed', [
                'file_id' => $data->getModel()->id,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
