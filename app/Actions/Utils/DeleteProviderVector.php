<?php

namespace App\Actions\Utils;

use Illuminate\Support\Facades\Log;
use Laravel\Ai\Stores;
use Throwable;

class DeleteProviderVector
{
    public function __invoke(string $vector): bool
    {
        try {
            return Stores::delete($vector);
        } catch (Throwable $exception) {
            Log::error('Genie provider vector store delete failed', [
                'provider_id' => $vector,
                'exception' => $exception->getMessage(),
            ]);
        }

        return false;
    }
}
