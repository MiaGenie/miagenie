<?php

use App\Enums\Modality;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per model call a workspace paid for, written by App\Listeners\RecordTokenUsage from
     * the SDK's AgentPrompted / ImageGenerated events. `input_tokens` includes cached input and
     * `output_tokens` includes reasoning (see TokenUsage::countsFrom()), so a call's cost is their
     * sum; the workspace counters (week / month / total) add them up.
     *
     * Text steps called before this table existed are backfilled from the SDK's conversation
     * messages, which kept their usage; image calls were never kept and stay uncounted. The
     * stored usage is read directly rather than through the SDK's Usage class, whose shape
     * changed in laravel/ai 1.0 (see countsFromStoredUsage()).
     */
    public function up(): void
    {
        Schema::create('genie_token_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('mixpost_workspaces')->cascadeOnDelete();
            $table->tinyInteger('modality')->default(Modality::TEXT->value);
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->timestamps();

            $table->index(['workspace_id', 'created_at']);
        });

        $this->backfillTextSteps();
    }

    public function down(): void
    {
        Schema::dropIfExists('genie_token_usages');
    }

    /**
     * Copy the usage of every assistant message written in a run's conversation. The workspace
     * comes from the run, not from the message's participant columns, so it does not depend on
     * the morph class they stored.
     *
     * On a fresh database this runs before the SDK's published 2026_08_21 migration has created
     * the messages table, and there is nothing to copy.
     */
    protected function backfillTextSteps(): void
    {
        $messagesTable = config('ai.conversations.tables.messages', 'agent_conversation_messages');

        if (! Schema::hasTable($messagesTable)) {
            return;
        }

        $workspaceByConversation = DB::table('genie_ai_runs')
            ->whereNotNull('conversation_id')
            ->groupBy('conversation_id')
            ->select('conversation_id', DB::raw('min(workspace_id) as workspace_id'));

        DB::table($messagesTable.' as m')
            ->joinSub($workspaceByConversation, 'r', 'r.conversation_id', '=', 'm.conversation_id')
            ->where('m.role', 'assistant')
            ->select('m.id', 'm.usage', 'm.meta', 'm.created_at', 'm.updated_at', 'r.workspace_id')
            ->chunkById(500, function (Collection $messages): void {
                DB::table('genie_token_usages')->insert(
                    $messages->map(fn (object $message): array => $this->usageRow($message))->all()
                );
            }, 'm.id', 'id');
    }

    /**
     * @return array<string, mixed>
     */
    protected function usageRow(object $message): array
    {
        $usage = json_decode($message->usage ?: '[]', true) ?: [];
        $meta = json_decode($message->meta ?: '[]', true) ?: [];
        $provider = $meta['provider'] ?? null;

        return [
            'workspace_id' => $message->workspace_id,
            'modality' => Modality::TEXT->value,
            'provider' => $provider,
            'model' => $meta['model'] ?? null,
            ...$this->countsFromStoredUsage($usage, $provider),
            'created_at' => $message->created_at,
            'updated_at' => $message->updated_at,
        ];
    }

    /**
     * What a stored message's usage cost on each side, in whichever format the SDK wrote it.
     *
     * laravel/ai 1.0 stores `input_tokens` / `output_tokens`, the provider's complete counts. Rows
     * written by 0.11.0, the only 0.x version this application ran, keep `prompt_tokens` /
     * `completion_tokens`, which that version filled differently per gateway:
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
