<?php

namespace Tests\Unit;

use App\Enums\Modality;
use App\Models\TokenUsage;
use App\Models\Workspace;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Ai\Responses\Data\ImageUsage;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\Data\Usage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * What a call costs and how a workspace's calls add up into its week / month / total counters.
 * The suite has no migrated database, so the table is built in memory; no workspace is current,
 * as in the admin panel.
 */
class TokenUsageTest extends TestCase
{
    protected Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.token_usage', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        config()->set('database.default', 'token_usage');

        Schema::create('genie_token_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id');
            $table->tinyInteger('modality')->default(Modality::TEXT->value);
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->timestamps();
        });

        $this->workspace = (new Workspace)->forceFill(['id' => 1]);
    }

    /**
     * @return array<string, array{Usage, array{input_tokens: int, output_tokens: int}}>
     */
    public static function usages(): array
    {
        return [
            'a text call costs its totals, whose cache and reasoning are already inside them' => [
                new TextUsage(inputTokens: 130, outputTokens: 50, cacheReadInputTokens: 20, cacheWriteInputTokens: 10, reasoningTokens: 30),
                ['input_tokens' => 130, 'output_tokens' => 50],
            ],
            'an image call costs its totals, image tokens included' => [
                new ImageUsage(inputTokens: 120, outputTokens: 4000, imageInputTokens: 100, imageOutputTokens: 4000),
                ['input_tokens' => 120, 'output_tokens' => 4000],
            ],
            'a usage without a breakdown costs its totals' => [
                new Usage(inputTokens: 7, outputTokens: 3),
                ['input_tokens' => 7, 'output_tokens' => 3],
            ],
            'an empty usage costs nothing' => [
                new TextUsage,
                ['input_tokens' => 0, 'output_tokens' => 0],
            ],
        ];
    }

    /**
     * @param  array{input_tokens: int, output_tokens: int}  $expected
     */
    #[DataProvider('usages')]
    public function test_a_call_costs_the_totals_the_sdk_reports(Usage $usage, array $expected): void
    {
        $this->assertSame($expected, TokenUsage::countsFrom($usage));
    }

    public function test_the_counters_follow_the_calendar_week_and_month(): void
    {
        $this->usageAt('2026-11-16 00:00:00', input: 1, output: 2);
        $this->usageAt('2026-11-15 23:59:59', input: 10, output: 20);
        $this->usageAt('2026-11-01 00:00:00', input: 100, output: 200);
        $this->usageAt('2026-10-31 23:59:59', input: 1000, output: 2000);

        $this->travelTo('2026-11-18 12:00:00');

        $this->assertSame(
            ['weekly' => 3, 'monthly' => 333, 'total' => 3333],
            TokenUsage::totalsFor($this->workspace),
        );
    }

    public function test_a_week_that_started_last_month_still_counts_its_first_days(): void
    {
        $this->usageAt('2026-11-30 09:00:00', input: 40, output: 2);
        $this->usageAt('2026-12-01 09:00:00', input: 5, output: 0);

        $this->travelTo('2026-12-01 18:00:00');

        $this->assertSame(
            ['weekly' => 47, 'monthly' => 5, 'total' => 47],
            TokenUsage::totalsFor($this->workspace),
        );
    }

    public function test_other_workspaces_are_not_counted(): void
    {
        $this->travelTo('2026-11-18 12:00:00');

        $this->usageAt('2026-11-18 10:00:00', input: 8, output: 1);
        $this->usageAt('2026-11-18 10:00:00', input: 5000, output: 5000, workspaceId: 2);

        $this->assertSame(
            ['weekly' => 9, 'monthly' => 9, 'total' => 9],
            TokenUsage::totalsFor($this->workspace),
        );
    }

    public function test_a_workspace_without_calls_has_zero_everywhere(): void
    {
        $this->usageAt('2026-11-18 10:00:00', input: 5, output: 5, workspaceId: 2);

        $this->assertSame(
            ['weekly' => 0, 'monthly' => 0, 'total' => 0],
            TokenUsage::totalsFor($this->workspace),
        );
    }

    protected function usageAt(string $at, int $input, int $output, int $workspaceId = 1): TokenUsage
    {
        $this->travelTo($at);

        $usage = TokenUsage::create([
            'workspace_id' => $workspaceId,
            'modality' => Modality::TEXT,
            'provider' => 'openai',
            'model' => 'gpt-5',
            'input_tokens' => $input,
            'output_tokens' => $output,
        ]);

        $this->travelBack();

        return $usage;
    }
}
