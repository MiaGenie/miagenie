<?php

namespace Tests\Unit;

use App\Enums\GenieSyncAction;
use App\Enums\GenieType;
use Closure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Tests\TestCase;

/**
 * Migration 0056 adds the token columns to genie_logs and fills them for the text calls logged
 * before, from the usage their conversation message kept. The suite has no migrated database, so
 * the tables are built in memory.
 */
class GenieLogTokenBackfillTest extends TestCase
{
    protected Migration $migration;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.log_token_backfill', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        config()->set('database.default', 'log_token_backfill');

        Schema::create('genie_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type');
            $table->string('action');
            $table->unsignedBigInteger('run_step_id')->nullable();
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->smallInteger('duration')->nullable();
            $table->timestamps();
        });

        $this->migration = require database_path('migrations/0056_add_token_counts_to_genie_logs_table.php');
    }

    public function test_text_calls_get_the_counts_of_their_message_in_either_stored_format(): void
    {
        $this->createMessagesTable();

        $this->message('m-1x', ['input_tokens' => 2280, 'output_tokens' => 829, 'cache_write_input_tokens' => 2277, 'reasoning_tokens' => 410], 'openai');
        $this->message('m-011-openai', ['prompt_tokens' => 100, 'completion_tokens' => 50, 'cache_read_input_tokens' => 20, 'cache_write_input_tokens' => 10, 'reasoning_tokens' => 30], 'openai');
        $this->message('m-011-gemini', ['prompt_tokens' => 100, 'completion_tokens' => 50, 'reasoning_tokens' => 30], 'gemini');

        $this->entry(1, ['message_id' => 'm-1x', 'structured' => ['summary' => 'ok']]);
        $this->entry(2, ['message_id' => 'm-011-openai']);
        $this->entry(3, ['message_id' => 'm-011-gemini']);

        $this->migration->up();

        $this->assertSame(['input_tokens' => 2280, 'output_tokens' => 829], $this->counts(1));
        $this->assertSame(['input_tokens' => 130, 'output_tokens' => 50], $this->counts(2));
        $this->assertSame(['input_tokens' => 100, 'output_tokens' => 80], $this->counts(3));
    }

    public function test_image_calls_failed_calls_and_calls_whose_message_is_gone_stay_unknown(): void
    {
        $this->createMessagesTable();

        $this->entry(1, ['file' => 'abc', 'position' => 1]);
        $this->entry(2, ['error' => 'Provider down.']);
        $this->entry(3, ['message_id' => 'deleted-message']);
        $this->entry(4, null);

        $this->migration->up();

        foreach ([1, 2, 3, 4] as $id) {
            $this->assertSame(['input_tokens' => null, 'output_tokens' => null], $this->counts($id));
        }
    }

    public function test_without_the_messages_table_only_the_columns_are_added(): void
    {
        $this->entry(1, ['message_id' => 'm-1x']);

        $this->migration->up();

        $this->assertTrue(Schema::hasColumns('genie_logs', ['input_tokens', 'output_tokens']));
        $this->assertSame(['input_tokens' => null, 'output_tokens' => null], $this->counts(1));
    }

    public function test_rolling_back_drops_the_columns(): void
    {
        $this->migration->up();
        $this->migration->down();

        $this->assertFalse(Schema::hasColumn('genie_logs', 'input_tokens'));
        $this->assertFalse(Schema::hasColumn('genie_logs', 'output_tokens'));
    }

    /**
     * The same table of cases as migration 0053, so the two copies of the rules cannot drift.
     *
     * @param  array<string, int>  $usage
     * @param  array{input_tokens: int, output_tokens: int}  $expected
     */
    #[DataProviderExternal(TokenUsageBackfillTest::class, 'storedUsages')]
    public function test_stored_usage_is_counted_as_migration_0053_counts_it(array $usage, ?string $provider, array $expected): void
    {
        config()->set('ai.providers.my-llm', ['driver' => 'openai-compatible', 'url' => 'http://localhost']);

        $counts = Closure::bind(
            fn (array $usage, ?string $provider): array => $this->countsFromStoredUsage($usage, $provider),
            $this->migration,
            $this->migration::class,
        );

        $this->assertSame($expected, $counts($usage, $provider));
    }

    protected function createMessagesTable(): void
    {
        Schema::create('agent_conversation_messages', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('conversation_id', 36);
            $table->string('role', 25);
            $table->text('content');
            $table->text('usage');
            $table->text('meta');
            $table->timestamps();
        });
    }

    /**
     * @param  array<string, int>  $usage
     */
    protected function message(string $id, array $usage, string $provider): void
    {
        DB::table('agent_conversation_messages')->insert([
            'id' => $id,
            'conversation_id' => 'conversation-1',
            'role' => 'assistant',
            'content' => '{}',
            'usage' => json_encode($usage),
            'meta' => json_encode(['provider' => $provider, 'model' => 'some-model']),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $response
     */
    protected function entry(int $id, ?array $response): void
    {
        DB::table('genie_logs')->insert([
            'id' => $id,
            'uuid' => (string) Str::uuid(),
            'type' => GenieType::RUN_RESPONSE->value,
            'action' => GenieSyncAction::CREATE->value,
            'response' => $response === null ? null : json_encode($response),
            'duration' => 3,
        ]);
    }

    /**
     * @return array{input_tokens: int|null, output_tokens: int|null}
     */
    protected function counts(int $id): array
    {
        $row = DB::table('genie_logs')->where('id', $id)->first(['input_tokens', 'output_tokens']);

        return [
            'input_tokens' => $row->input_tokens === null ? null : (int) $row->input_tokens,
            'output_tokens' => $row->output_tokens === null ? null : (int) $row->output_tokens,
        ];
    }
}
