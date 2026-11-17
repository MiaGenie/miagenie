<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What one logged model call consumed: the SDK's input total (cache included) and output
     * total (reasoning included), counted by TokenUsage::countsFrom() as the workspace ledger is.
     * A run step's and a run's tokens are the sums of their entries. Null means unknown — no
     * call answered, or the entry is not a model call.
     *
     * Text calls logged before this are backfilled from the SDK's conversation messages, which
     * kept their usage; image calls never kept it and stay null. The stored usage is read
     * directly rather than through the SDK (see countsFromStoredUsage()).
     */
    public function up(): void
    {
        Schema::table('genie_logs', function (Blueprint $table) {
            $table->unsignedInteger('input_tokens')->nullable()->after('duration');
            $table->unsignedInteger('output_tokens')->nullable()->after('input_tokens');
        });

        $this->backfillTextCalls();
    }

    public function down(): void
    {
        Schema::table('genie_logs', function (Blueprint $table) {
            $table->dropColumn(['input_tokens', 'output_tokens']);
        });
    }

    /**
     * Copy the usage of the message each text call wrote onto its entry.
     *
     * Only the message id is read from the entries: their payloads can be hundreds of kilobytes.
     * On a fresh database this runs before the SDK's published 2026_08_21 migration has created
     * the messages table, and there is nothing to copy.
     */
    protected function backfillTextCalls(): void
    {
        $messagesTable = config('ai.conversations.tables.messages', 'agent_conversation_messages');

        if (! Schema::hasTable($messagesTable)) {
            return;
        }

        DB::table('genie_logs')
            ->whereNotNull('response->message_id')
            ->select('id', 'response->message_id as message_id')
            ->chunkById(500, function (Collection $entries) use ($messagesTable): void {
                $messages = DB::table($messagesTable)
                    ->whereIn('id', $entries->pluck('message_id')->filter()->unique()->all())
                    ->get(['id', 'usage', 'meta'])
                    ->keyBy('id');

                foreach ($entries as $entry) {
                    $message = $messages->get($entry->message_id);

                    if (! $message) {
                        continue;
                    }

                    $usage = json_decode($message->usage ?: '[]', true) ?: [];
                    $meta = json_decode($message->meta ?: '[]', true) ?: [];

                    DB::table('genie_logs')
                        ->where('id', $entry->id)
                        ->update($this->countsFromStoredUsage($usage, $meta['provider'] ?? null));
                }
            });
    }

    /**
     * What a stored message's usage cost on each side, in whichever format the SDK wrote it.
     *
     * A copy of migration 0053's rules, kept here so this migration does not depend on a class
     * that may change. laravel/ai 1.0 stores `input_tokens` / `output_tokens`, the provider's
     * complete counts. Rows written by 0.11.0 keep `prompt_tokens` / `completion_tokens`:
     *
     * - OpenRouter, Groq and openai-compatible drivers stored the provider's inclusive prompt count,
     *   so their cache counters are already inside it; every other gateway subtracted cached and
     *   cache-written input, which is added back.
     * - Gemini and xAI reported reasoning apart from the answer, so it is added to the output;
     *   elsewhere it is already part of the completion count.
     *
     * @param  array<string, mixed>  $usage
     * @return array{input_tokens: int, output_tokens: int}
     */
    protected function countsFromStoredUsage(array $usage, ?string $provider): array
    {
        if (array_key_exists('input_tokens', $usage) || array_key_exists('output_tokens', $usage)) {
            return [
                'input_tokens' => (int) ($usage['input_tokens'] ?? 0),
                'output_tokens' => (int) ($usage['output_tokens'] ?? 0),
            ];
        }

        $promptIncludesCache = in_array($provider, ['openrouter', 'groq'], true)
            || ($provider !== null && config("ai.providers.{$provider}.driver") === 'openai-compatible');

        $reasoningIsSeparate = in_array($provider, ['gemini', 'xai'], true);

        $cachedInput = (int) ($usage['cache_read_input_tokens'] ?? 0) + (int) ($usage['cache_write_input_tokens'] ?? 0);

        return [
            'input_tokens' => (int) ($usage['prompt_tokens'] ?? 0) + ($promptIncludesCache ? 0 : $cachedInput),
            'output_tokens' => (int) ($usage['completion_tokens'] ?? 0)
                + ($reasoningIsSeparate ? (int) ($usage['reasoning_tokens'] ?? 0) : 0),
        ];
    }
};
