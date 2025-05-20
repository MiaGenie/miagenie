<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('genie_threads', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('workspace_id')->constrained('mixpost_workspaces');
            $table->foreignId('rule_id')->constrained('genie_rules');
            $table->string('thread_provider_id');
            $table->timestamps();
        });

        Schema::create('genie_thread_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('thread_id')->constrained('genie_threads')->onDelete('cascade');
            $table->foreignId('step_id')->constrained('genie_rule_steps')->onDelete('cascade');
            $table->integer('max_completion_tokens');
            $table->integer('max_prompt_tokens');
            $table->tinyInteger('status');
            $table->tinyInteger('status_provider');
            $table->tinyInteger('error_provider');
            $table->tinyInteger('incomplete_details_provider');
            $table->json('message')->nullable();
            $table->timestamps();
        });

        Schema::create('genie_thread_briefings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->unique()->constrained('genie_threads')->onDelete('cascade');
            $table->foreignId('briefing_id')->constrained('genie_briefings')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('genie_run_competitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('genie_thread_runs')->onDelete('cascade');
            $table->foreignId('competitor_id')->constrained('genie_competitors')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('genie_run_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('thread_id')->constrained('genie_threads')->onDelete('cascade');
            $table->foreignId('run_id')->constrained('genie_runs')->onDelete('cascade');
            $table->json('data')->nullable();
            $table->json('response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('genie_run_logs');
        Schema::dropIfExists('genie_run_competitors');
        Schema::dropIfExists('genie_thread_briefings');
        Schema::dropIfExists('genie_thread_runs');
        Schema::dropIfExists('genie_threads');
    }
};
