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

        Schema::create('genie_pre_posts', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('workspace_id')->constrained('mixpost_workspaces')->onDelete('cascade');
            $table->foreignId('draft_id')->nullable()->constrained('genie_drafts')->nullOnDelete();
            $table->foreignId('post_id')->nullable()->constrained('mixpost_posts')->nullOnDelete();
            $table->text('caption')->nullable();
            $table->tinyInteger('status');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('genie_pre_post_run_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pre_post_id')->constrained('genie_pre_posts')->onDelete('cascade');
            $table->foreignId('run_response_id')->constrained('genie_run_responses')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('genie_run_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('genie_runs')->onDelete('cascade');
            $table->foreignId('draft_id')->nullable()->constrained('genie_drafts')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('genie_run_draft_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_draft_id')->constrained('genie_run_drafts')->onDelete('cascade');
            $table->foreignId('run_response_id')->constrained('genie_run_responses')->onDelete('cascade');
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
        Schema::dropIfExists('genie_run_draft_responses');
        Schema::dropIfExists('genie_run_drafts');
        Schema::dropIfExists('genie_pre_post_run_responses');
        Schema::dropIfExists('genie_pre_posts');

    }
};
