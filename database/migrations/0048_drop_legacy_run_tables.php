<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The first generation engine (genie_runs / genie_run_responses and the drafts, pre-posts and
 * competitors built on it) was replaced by the AiRun pipeline (genie_ai_runs). Nothing writes or
 * reads these tables any more, so they go, together with the idea's link to a run response.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('genie_ideas', 'run_response_id')) {
            Schema::table('genie_ideas', function (Blueprint $table) {
                $table->dropConstrainedForeignId('run_response_id');
            });
        }

        // Migration 0035 dropped genie_strategies.run_id, but a database that ran it on MySQL may
        // still carry the constraint (and the column) that pointed at genie_runs.
        if ($this->hasForeignKey('genie_strategies', 'genie_strategies_run_id_foreign')) {
            Schema::table('genie_strategies', function (Blueprint $table) {
                $table->dropForeign('genie_strategies_run_id_foreign');
            });
        }

        if (Schema::hasColumn('genie_strategies', 'run_id')) {
            Schema::table('genie_strategies', function (Blueprint $table) {
                $table->dropColumn('run_id');
            });
        }

        Schema::dropIfExists('genie_pre_post_run_responses');
        Schema::dropIfExists('genie_run_draft_responses');
        Schema::dropIfExists('genie_run_drafts');
        Schema::dropIfExists('genie_pre_posts');
        Schema::dropIfExists('genie_drafts');
        Schema::dropIfExists('genie_run_response_workspace_file');
        Schema::dropIfExists('genie_run_field_iterators');
        Schema::dropIfExists('genie_run_idea_responses');
        Schema::dropIfExists('genie_run_ideas');
        Schema::dropIfExists('genie_run_strategy');
        Schema::dropIfExists('genie_run_response_reviews');
        Schema::dropIfExists('genie_run_competitors');
        Schema::dropIfExists('genie_run_briefings');
        Schema::dropIfExists('genie_run_responses');
        Schema::dropIfExists('genie_runs');
        Schema::dropIfExists('genie_competitors');
    }

    private function hasForeignKey(string $table, string $name): bool
    {
        return collect(Schema::getForeignKeys($table))->contains(fn (array $key) => $key['name'] === $name);
    }

    /**
     * Reverse the migrations.
     *
     * Recreates the table structures only; the rows are not recoverable.
     */
    public function down(): void
    {
        Schema::create('genie_competitors', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('workspace_id')->constrained('mixpost_workspaces')->onDelete('cascade');
            $table->json('content')->nullable();
            $table->foreignId('version_id')->constrained('genie_versions');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('genie_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('workspace_id')->nullable()->constrained('mixpost_workspaces')->nullOnDelete();
            $table->foreignId('rule_id')->nullable()->constrained('genie_rules')->nullOnDelete();
            $table->tinyInteger('status')->default(1);
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
            $table->tinyInteger('status')->default(1);
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

        Schema::create('genie_run_strategy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('genie_runs')->onDelete('cascade');
            $table->foreignId('strategy_id')->nullable()->constrained('genie_strategies')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('genie_run_ideas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('genie_runs')->onDelete('cascade');
            $table->foreignId('idea_id')->nullable()->constrained('genie_ideas')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('genie_run_idea_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_idea_id')->constrained('genie_run_ideas')->onDelete('cascade');
            $table->foreignId('run_response_id')->constrained('genie_run_responses')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('genie_run_field_iterators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_response_id')->constrained('genie_run_responses')->onDelete('cascade');
            $table->foreignId('field_id')->nullable()->constrained('genie_version_fields')->nullOnDelete();
            $table->integer('field_index');
            $table->timestamps();
        });

        Schema::create('genie_run_response_workspace_file', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_response_id')->constrained('genie_run_responses')->onDelete('cascade');
            $table->foreignId('workspace_file_id')->nullable()->constrained('genie_workspace_files')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('genie_drafts', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('workspace_id')->constrained('mixpost_workspaces')->onDelete('cascade');
            $table->foreignId('run_response_id')->nullable()->constrained('genie_run_responses')->nullOnDelete();
            $table->foreignId('idea_id')->nullable()->constrained('genie_ideas')->nullOnDelete();
            $table->string('topic');
            $table->text('goal');
            $table->text('key_ideas');
            $table->text('media');
            $table->tinyInteger('status');
            $table->softDeletes();
            $table->timestamps();
        });

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

        Schema::create('genie_pre_post_run_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pre_post_id')->constrained('genie_pre_posts')->onDelete('cascade');
            $table->foreignId('run_response_id')->constrained('genie_run_responses')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::table('genie_ideas', function (Blueprint $table) {
            $table->foreignId('run_response_id')->nullable()->after('workspace_id')->constrained('genie_run_responses')->nullOnDelete();
        });
    }
};
