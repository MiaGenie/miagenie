<?php

namespace Tests\Unit;

use Closure;
use Illuminate\Database\Migrations\Migration;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Migration 0053 backfills token counts from the usage the SDK kept on conversation messages,
 * which laravel/ai 0.11.0 and 1.x wrote in different shapes.
 */
class TokenUsageBackfillTest extends TestCase
{
    /**
     * @return array<string, array{array<string, int>, ?string, array{input_tokens: int, output_tokens: int}}>
     */
    public static function storedUsages(): array
    {
        $legacy = [
            'prompt_tokens' => 100,
            'completion_tokens' => 50,
            'cache_read_input_tokens' => 20,
            'cache_write_input_tokens' => 10,
            'reasoning_tokens' => 30,
        ];

        return [
            '1.x totals are taken as they are' => [
                ['input_tokens' => 130, 'output_tokens' => 80, 'cache_read_input_tokens' => 20, 'reasoning_tokens' => 30],
                'openai',
                ['input_tokens' => 130, 'output_tokens' => 80],
            ],
            '0.11 openai had cache subtracted, reasoning inside the completion' => [
                $legacy,
                'openai',
                ['input_tokens' => 130, 'output_tokens' => 50],
            ],
            '0.11 anthropic had cache subtracted' => [
                $legacy,
                'anthropic',
                ['input_tokens' => 130, 'output_tokens' => 50],
            ],
            '0.11 gemini reported reasoning apart' => [
                $legacy,
                'gemini',
                ['input_tokens' => 130, 'output_tokens' => 80],
            ],
            '0.11 xai reported reasoning apart' => [
                $legacy,
                'xai',
                ['input_tokens' => 130, 'output_tokens' => 80],
            ],
            '0.11 openrouter kept cache inside the prompt' => [
                $legacy,
                'openrouter',
                ['input_tokens' => 100, 'output_tokens' => 50],
            ],
            '0.11 groq kept cache inside the prompt' => [
                $legacy,
                'groq',
                ['input_tokens' => 100, 'output_tokens' => 50],
            ],
            '0.11 openai-compatible kept cache inside the prompt' => [
                $legacy,
                'my-llm',
                ['input_tokens' => 100, 'output_tokens' => 50],
            ],
            'a message without usage cost nothing' => [
                [],
                null,
                ['input_tokens' => 0, 'output_tokens' => 0],
            ],
        ];
    }

    /**
     * @param  array<string, int>  $usage
     * @param  array{input_tokens: int, output_tokens: int}  $expected
     */
    #[DataProvider('storedUsages')]
    public function test_stored_usage_is_counted_the_way_the_sdk_that_wrote_it_meant(array $usage, ?string $provider, array $expected): void
    {
        config()->set('ai.providers.my-llm', ['driver' => 'openai-compatible', 'url' => 'http://localhost']);

        /** @var Migration $migration */
        $migration = require database_path('migrations/0053_create_genie_token_usages_table.php');

        $counts = Closure::bind(
            fn (array $usage, ?string $provider): array => $this->countsFromStoredUsage($usage, $provider),
            $migration,
            $migration::class,
        );

        $this->assertSame($expected, $counts($usage, $provider));
    }
}
