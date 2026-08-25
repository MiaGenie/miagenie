<?php

use App\Enums\Modality;
use App\Enums\RunStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The strategy run tables, rebuilt around the AI SDK.
     */
    public function up(): void
    {
        Schema::create('genie_ai_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('workspace_id')->constrained('mixpost_workspaces')->cascadeOnDelete();
            $table->foreignId('rule_id')->nullable()->constrained('genie_rules')->nullOnDelete();

            // Both were 1:1 pivot tables of their own (genie_run_briefings, genie_run_strategy).
            $table->foreignId('briefing_id')->nullable()->constrained('genie_briefings')->nullOnDelete();
            $table->foreignId('strategy_id')->nullable()->constrained('genie_strategies')->nullOnDelete();

            // The agent_conversations row whose messages carry this run's steps forward.
            $table->char('conversation_id', 36)->nullable()->index();

            $table->tinyInteger('status')->default(RunStatus::OPEN);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
        });

        Schema::create('genie_ai_run_steps', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('run_id')->constrained('genie_ai_runs')->cascadeOnDelete();
            $table->foreignId('step_id')->nullable()->constrained('genie_rule_steps')->nullOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->tinyInteger('modality')->default(Modality::TEXT);
            $table->tinyInteger('status')->default(RunStatus::OPEN);
            $table->string('invocation_id')->nullable();
            $table->char('message_id', 36)->nullable()->index();
            $table->json('output')->nullable();
            $table->tinyInteger('error')->nullable();
            $table->string('error_details')->nullable();
            $table->unsignedInteger('duration')->nullable();
            $table->timestamps();

            $table->index(['run_id', 'position']);
        });

        Schema::create('genie_ai_run_step_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_step_id')->constrained('genie_ai_run_steps')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('original');
            $table->json('reviewed');
            $table->timestamps();
        });

        Schema::create('genie_ai_run_step_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_step_id')->constrained('genie_ai_run_steps')->cascadeOnDelete();
            $table->foreignId('workspace_file_id')->constrained('genie_workspace_files')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['run_step_id', 'workspace_file_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('genie_ai_run_step_files');
        Schema::dropIfExists('genie_ai_run_step_reviews');
        Schema::dropIfExists('genie_ai_run_steps');
        Schema::dropIfExists('genie_ai_runs');
    }
};
