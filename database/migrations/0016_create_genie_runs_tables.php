<?php

use App\Enums\RunStatus;
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
        Schema::create('genie_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('workspace_id')->nullable()->constrained('mixpost_workspaces')->nullOnDelete();
            $table->foreignId('rule_id')->nullable()->constrained('genie_rules')->nullOnDelete();
            $table->tinyInteger('status')->default(RunStatus::OPEN);
            $table->timestamps();
        });

        Schema::create('genie_run_responses', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('run_id')->constrained('genie_runs')->onDelete('cascade');
            $table->foreignId('step_id')->nullable()->constrained('genie_rule_steps')->nullOnDelete();
            $table->string('response_provider_id')->nullable();
            $table->tinyInteger('provider_status')->nullable();
            $table->tinyInteger('error')->nullable();
            $table->string('error_details')->nullable();
            $table->string('incomplete_details')->nullable();
            $table->json('output')->nullable();
            $table->text('output_text')->nullable();
            $table->tinyInteger('status')->default(RunStatus::OPEN);
            $table->timestamps();
        });

        Schema::create('genie_run_briefings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->unique()->constrained('genie_runs')->onDelete('cascade');
            $table->foreignId('briefing_id')->nullable()->constrained('genie_briefings')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('genie_run_competitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_response_id')->constrained('genie_run_responses')->onDelete('cascade');
            $table->foreignId('competitor_id')->nullable()->constrained('genie_competitors')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('genie_run_response_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_response_id')->constrained('genie_run_responses')->onDelete('cascade');
            $table->json('original');
            $table->json('reviewed');
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
        Schema::dropIfExists('genie_run_response_reviews');
        Schema::dropIfExists('genie_run_competitors');
        Schema::dropIfExists('genie_run_briefings');
        Schema::dropIfExists('genie_run_responses');
        Schema::dropIfExists('genie_runs');
    }
};
