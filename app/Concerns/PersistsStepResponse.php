<?php

namespace App\Concerns;

use App\Ai\StepResponse;
use App\Models\RunResponse;

trait PersistsStepResponse
{
    /**
     * Store the provider-agnostic response fields on the run response.
     *
     * @param  array<string, mixed>  $response
     */
    protected function persistResponse(RunResponse $model, array $response): void
    {
        $model->update([
            'provider_status' => StepResponse::status($response['status'] ?? null),
            'output' => $response['structured'] ?? null,
            'output_text' => $response['text'] ?? null,
            'error' => StepResponse::error($response['error']['code'] ?? null),
            'error_details' => isset($response['error']['message'])
                ? mb_substr((string) $response['error']['message'], 0, 255)
                : null,
        ]);
    }

    /**
     * The decoded payload a handler should write.
     *
     * Structured steps get the SDK's parsed array directly. Text steps are decoded from the
     * response text, which is also the fallback when a structured step returns nothing usable.
     *
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>|null
     */
    protected function structuredOutput(array $response): ?array
    {
        if (is_array($response['structured'] ?? null)) {
            return $response['structured'];
        }

        $decoded = json_decode((string) ($response['text'] ?? ''), true);

        return is_array($decoded) ? $decoded : null;
    }
}
