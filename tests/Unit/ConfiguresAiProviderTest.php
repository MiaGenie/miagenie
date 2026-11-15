<?php

namespace Tests\Unit;

use App\Concerns\ConfiguresAiProvider;
use App\Configs\AnthropicConfig;
use App\Configs\DeepSeekConfig;
use App\Configs\GeminiConfig;
use App\Configs\OpenAIConfig;
use Laravel\Ai\Enums\Lab;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ConfiguresAiProviderTest extends TestCase
{
    /**
     * Bind a config whose stored values are fixed, so the trait never reaches the database.
     *
     * @param  class-string  $class
     * @param  array<string, mixed>  $values
     */
    protected function storeConfig(string $class, array $values): void
    {
        $config = $this->createPartialMock($class, ['get']);
        $config->method('get')->willReturnCallback(fn (string $name) => $values[$name] ?? null);

        $this->app->instance($class, $config);
    }

    protected function configure(Lab|string $provider): void
    {
        $subject = new class
        {
            use ConfiguresAiProvider;

            public function run(Lab|string $provider): void
            {
                $this->configureAiProvider($provider);
            }
        };

        $subject->run($provider);
    }

    /**
     * @return array<string, array{class-string, Lab}>
     */
    public static function databaseBackedProviders(): array
    {
        return [
            'openai' => [OpenAIConfig::class, Lab::OpenAI],
            'anthropic' => [AnthropicConfig::class, Lab::Anthropic],
            'gemini' => [GeminiConfig::class, Lab::Gemini],
            'deepseek' => [DeepSeekConfig::class, Lab::DeepSeek],
        ];
    }

    #[DataProvider('databaseBackedProviders')]
    public function test_the_stored_key_replaces_the_environment_key(string $class, Lab $lab): void
    {
        config(["ai.providers.{$lab->value}.key" => 'env-key']);
        $this->storeConfig($class, ['api_key' => 'stored-key']);

        $this->configure($lab);

        $this->assertSame('stored-key', config("ai.providers.{$lab->value}.key"));
    }

    #[DataProvider('databaseBackedProviders')]
    public function test_the_provider_can_be_named_by_its_string_value(string $class, Lab $lab): void
    {
        config(["ai.providers.{$lab->value}.key" => 'env-key']);
        $this->storeConfig($class, ['api_key' => 'stored-key']);

        $this->configure($lab->value);

        $this->assertSame('stored-key', config("ai.providers.{$lab->value}.key"));
    }

    #[DataProvider('databaseBackedProviders')]
    public function test_a_blank_stored_key_keeps_the_environment_key(string $class, Lab $lab): void
    {
        foreach ([null, ''] as $blank) {
            config(["ai.providers.{$lab->value}.key" => 'env-key']);
            $this->storeConfig($class, ['api_key' => $blank]);

            $this->configure($lab);

            $this->assertSame('env-key', config("ai.providers.{$lab->value}.key"));
        }
    }

    public function test_only_the_requested_provider_is_configured(): void
    {
        config(['ai.providers.anthropic.key' => 'env-anthropic', 'ai.providers.gemini.key' => 'env-gemini']);
        $this->storeConfig(AnthropicConfig::class, ['api_key' => 'stored-anthropic']);
        $this->storeConfig(GeminiConfig::class, ['api_key' => 'stored-gemini']);

        $this->configure(Lab::Anthropic);

        $this->assertSame('stored-anthropic', config('ai.providers.anthropic.key'));
        $this->assertSame('env-gemini', config('ai.providers.gemini.key'));
    }

    public function test_a_provider_without_a_stored_credential_is_left_alone(): void
    {
        config(['ai.providers.groq.key' => 'env-groq']);

        $this->configure(Lab::Groq);

        $this->assertSame('env-groq', config('ai.providers.groq.key'));
    }
}
