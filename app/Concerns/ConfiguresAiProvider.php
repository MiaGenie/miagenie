<?php

namespace App\Concerns;

use App\Configs\OpenAIConfig;
use Laravel\Ai\Ai;
use Laravel\Ai\Enums\Lab;

/**
 * Bridges this application's database-held credentials into the AI SDK's provider config.
 *
 * The SDK reads `config('ai.providers.<name>')` when it resolves a provider, but the OpenAI
 * key lives encrypted in the database so it stays editable from the admin UI. Pushing the
 * value into config just before prompting keeps both true.
 */
trait ConfiguresAiProvider
{
    protected function configureAiProvider(Lab|string $provider): void
    {
        $name = $provider instanceof Lab ? $provider->value : $provider;

        $key = match ($name) {
            Lab::OpenAI->value => app(OpenAIConfig::class)->all()['api_key'] ?? null,
            default => null,
        };

        // Providers without a database-backed credential keep whatever config/ai.php resolved
        // from the environment.
        if (blank($key) || config("ai.providers.$name.key") === $key) {
            return;
        }

        config(["ai.providers.$name.key" => $key]);

        // The manager caches resolved provider instances, so an instance built earlier in this
        // process would still hold the previous key.
        Ai::forgetInstance($name);
    }
}
