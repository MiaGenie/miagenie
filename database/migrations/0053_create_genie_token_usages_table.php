<?php

use App\Enums\Modality;
use App\Models\TokenUsage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Ai\Responses\Data\Usage;

return new class extends Migration
{
    /**
     * One row per model call a workspace paid for, written by App\Listeners\RecordTokenUsage from
     * the SDK's AgentPrompted / ImageGenerated events. `input_tokens` includes cached input and
     * `output_tokens` includes reasoning (see TokenUsage::countsFrom()), so a call's cost is their
     * sum; the workspace counters (week / month / total) add them up.
     *
     * Text steps called before this table existed are backfilled from the SDK's conversation
     * messages, which kept their usage; image calls were never kept and stay uncounted.
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
     */
    protected function backfillTextSteps(): void
    {
        $workspaceByConversation = DB::table('genie_ai_runs')
            ->whereNotNull('conversation_id')
            ->groupBy('conversation_id')
            ->select('conversation_id', DB::raw('min(workspace_id) as workspace_id'));

        DB::table(config('ai.conversations.tables.messages', 'agent_conversation_messages').' as m')
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
            ...TokenUsage::countsFrom(new Usage(
                (int) ($usage['prompt_tokens'] ?? 0),
                (int) ($usage['completion_tokens'] ?? 0),
                (int) ($usage['cache_write_input_tokens'] ?? 0),
                (int) ($usage['cache_read_input_tokens'] ?? 0),
                (int) ($usage['reasoning_tokens'] ?? 0),
            ), $provider),
            'created_at' => $message->created_at,
            'updated_at' => $message->updated_at,
        ];
    }
};
