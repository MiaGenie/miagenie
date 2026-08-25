<?php

namespace App\Actions\Utils;

use Illuminate\Support\Facades\Log;
use Laravel\Ai\Files;
use Throwable;

class DeleteProviderFile
{
    public function __invoke(string $file): bool
    {
        try {
            Files::delete($file);

            return true;
        } catch (Throwable $exception) {
            Log::error('Genie provider file delete failed', [
                'provider_id' => $file,
                'exception' => $exception->getMessage(),
            ]);
        }

        return false;
    }
}
