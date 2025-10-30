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
        Schema::create('genie_ideas', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('workspace_id')->constrained('mixpost_workspaces')->onDelete('cascade');
            $table->foreignId('strategy_id')->nullable()->constrained('genie_strategies')->nullOnDelete();
            $table->foreignId('run_response_id')->nullable()->constrained('genie_run_responses')->nullOnDelete();
            $table->string('theme');
            $table->text('description');
            $table->tinyInteger('status');
            $table->tinyInteger('source');
            $table->tinyInteger('funnel_stage')->nullable();
            $table->string('content_pillar')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('genie_drafts', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('workspace_id')->constrained('mixpost_workspaces')->onDelete('cascade');
            $table->foreignId('idea_id')->nullable()->constrained('genie_version_fields')->nullOnDelete();
            $table->string('goal');
            $table->text('caption');
            $table->text('media');
            $table->tinyInteger('status');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('genie_run_ideas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('genie_runs')->onDelete('cascade');
            $table->foreignId('strategy_id')->nullable()->constrained('genie_strategies')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('genie_run_field_iterators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_response_id')->constrained('genie_run_responses')->onDelete('cascade');
            $table->foreignId('field_id')->nullable()->constrained('genie_version_fields')->nullOnDelete();
            $table->integer('field_index');
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
        Schema::dropIfExists('genie_run_field_iterators');
        Schema::dropIfExists('genie_run_ideas');
        Schema::dropIfExists('genie_drafts');
        Schema::dropIfExists('genie_ideas');

    }
};
