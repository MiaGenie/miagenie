<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An idea is no longer a theme with a description that a draft step expands later: it carries
     * everything a post is produced from — objective, suggested content, multimedia suggestion and
     * production instructions — together with the format it targets and the run step that wrote it.
     */
    public function up(): void
    {
        Schema::table('genie_ideas', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('genie_ideas', function (Blueprint $table) {
            $table->foreignId('run_step_id')->nullable()->after('strategy_id')->constrained('genie_ai_run_steps')->nullOnDelete();
            $table->text('objective')->nullable()->after('theme');
            $table->text('suggested_content')->nullable()->after('objective');
            $table->text('multimedia_suggestion')->nullable()->after('suggested_content');
            $table->text('production_instructions')->nullable()->after('multimedia_suggestion');
            $table->tinyInteger('post_format')->nullable()->after('funnel_stage');
            $table->index(['workspace_id', 'post_format']);
        });
    }

    public function down(): void
    {
        Schema::table('genie_ideas', function (Blueprint $table) {
            $table->dropIndex(['workspace_id', 'post_format']);
            $table->dropConstrainedForeignId('run_step_id');
            $table->dropColumn(['objective', 'suggested_content', 'multimedia_suggestion', 'production_instructions', 'post_format']);
            $table->text('description')->nullable();
        });
    }
};
