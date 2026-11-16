<?php

use App\Enums\ModelTier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * laravel/ai 1.4 moved the default, cheapest and smartest text models of most providers to newer
 * models, and a tiered profile follows whatever its provider's tier resolves to. Each tiered
 * profile whose text model changes therefore gets a copy pinned (tier OTHER) to the model it ran
 * on under 0.11.0, so an admin can point a text step back at it.
 *
 * The 0.11.0 models are written out here because the installed SDK now reports the new ones.
 * Only tiers whose model changed are listed: Azure and openai-compatible take theirs from
 * config/ai.php, Groq's did not change, Cohere had no text models in 0.11.0 and Bedrock is not
 * configured; a tier pinned in config/ai.php is skipped too.
 *
 * A copy names a text model, which an image step would send as its image model, so the copies
 * are for text steps only. Image steps keep following the provider's default image model, which
 * 1.4 also moved for Gemini, xAI and OpenRouter (OpenAI and Azure are pinned in config/ai.php).
 */
return new class extends Migration
{
    /**
     * The text model each SDK driver's tier resolved to under laravel/ai 0.11.0, for the tiers 1.4
     * resolves differently.
     *
     * @var array<string, array<string, string>>
     */
    protected const PREVIOUS_MODELS = [
        'openai' => ['default' => 'gpt-5.4', 'cheapest' => 'gpt-5.4-nano', 'smartest' => 'gpt-5.4-pro'],
        'anthropic' => ['default' => 'claude-sonnet-5', 'cheapest' => 'claude-haiku-4-5-20251001', 'smartest' => 'claude-opus-4-8'],
        'gemini' => ['default' => 'gemini-3.7-flash', 'cheapest' => 'gemini-3.5-flash-lite', 'smartest' => 'gemini-3.7-flash'],
        'deepseek' => ['default' => 'deepseek-v4-flash', 'cheapest' => 'deepseek-v4-flash', 'smartest' => 'deepseek-reasoner'],
        'mistral' => ['default' => 'mistral-medium-latest', 'cheapest' => 'mistral-small-latest', 'smartest' => 'mistral-large-latest'],
        'ollama' => ['default' => 'llama3.1:8b', 'cheapest' => 'llama3.1:8b', 'smartest' => 'llama3.1:70b'],
        'openrouter' => ['default' => 'anthropic/claude-sonnet-4.6', 'smartest' => 'anthropic/claude-opus-4.6'],
        'xai' => ['default' => 'grok-4.20-non-reasoning', 'smartest' => 'grok-4.3'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $tiers = [ModelTier::DEFAULT->value, ModelTier::CHEAPEST->value, ModelTier::SMARTEST->value];

            $profiles = DB::table('genie_model_profiles')
                ->whereIn('model_tier', $tiers)
                ->orderBy('position')
                ->orderBy('id')
                ->get();

            $position = (int) DB::table('genie_model_profiles')->max('position');
            $now = now();

            foreach ($profiles as $profile) {
                $model = $this->previousModel($profile->provider, $profile->model_tier);

                if ($model === null) {
                    continue;
                }

                $name = $this->copyName($profile->name, $model);

                $copied = DB::table('genie_model_profiles')
                    ->where('name', $name)
                    ->where('provider', $profile->provider)
                    ->where('model_tier', ModelTier::OTHER->value)
                    ->where('model', $model)
                    ->exists();

                if ($copied) {
                    continue;
                }

                DB::table('genie_model_profiles')->insert([
                    'uuid' => (string) Str::uuid(),
                    'name' => $name,
                    'provider' => $profile->provider,
                    'model_tier' => ModelTier::OTHER->value,
                    'model' => $model,
                    'timeout' => $profile->timeout,
                    'position' => ++$position,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * The copies are ordinary profiles once created and may already have steps pointing at them,
     * so they are left for an admin to delete.
     */
    public function down(): void
    {
        //
    }

    /**
     * The 0.11.0 model of a profile's tier, or null when 1.4 resolves the tier to the same model.
     *
     * A profile's provider is a config/ai.php key; the SDK's defaults belong to the key's driver.
     */
    protected function previousModel(string $provider, string $tier): ?string
    {
        if (filled(config("ai.providers.{$provider}.models.text.{$tier}"))) {
            return null;
        }

        $driver = config("ai.providers.{$provider}.driver") ?? $provider;

        return self::PREVIOUS_MODELS[$driver][$tier] ?? null;
    }

    /**
     * The original name with the pinned model after it, within the column's 255 characters.
     */
    protected function copyName(string $name, string $model): string
    {
        $suffix = " ({$model})";

        return Str::substr($name, 0, 255 - mb_strlen($suffix)).$suffix;
    }
};
