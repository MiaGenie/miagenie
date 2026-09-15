<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A run step's files become versioned image slots.
     *
     * `position` is the 1-based slot the file fills (a carousel has several; the ideas UI calls
     * them "Image n"). `brief`, `prompt` and `feedback` record what was asked for that exact file,
     * so a regeneration can be understood and reproduced later. A regenerated slot keeps its
     * previous rows: the old one gets `superseded_at`, the new one points at it through
     * `parent_file_id` and says how it was made in `mode` (`fix` edits the previous file with
     * feedback, `rethink` starts over from a brief). `revising_at` / `revision_error` track a
     * regeneration in flight. Nothing in the first generation writes the revision columns.
     */
    public function up(): void
    {
        Schema::table('genie_ai_run_step_files', function (Blueprint $table) {
            $table->unsignedSmallInteger('position')->default(1)->after('workspace_file_id');
            $table->text('brief')->nullable()->after('position');
            $table->text('prompt')->nullable()->after('brief');
            $table->text('feedback')->nullable()->after('prompt');
            $table->string('mode', 20)->nullable()->after('feedback');
            $table->foreignId('parent_file_id')->nullable()->after('mode')
                ->constrained('genie_workspace_files')->nullOnDelete();
            $table->timestamp('superseded_at')->nullable()->after('parent_file_id');
            $table->timestamp('revising_at')->nullable()->after('superseded_at');
            $table->string('revision_error')->nullable()->after('revising_at');

            $table->index(['run_step_id', 'position', 'superseded_at'], 'genie_ai_run_step_files_slot_index');
        });
    }

    public function down(): void
    {
        Schema::table('genie_ai_run_step_files', function (Blueprint $table) {
            $table->dropIndex('genie_ai_run_step_files_slot_index');
            $table->dropConstrainedForeignId('parent_file_id');
            $table->dropColumn([
                'position',
                'brief',
                'prompt',
                'feedback',
                'mode',
                'superseded_at',
                'revising_at',
                'revision_error',
            ]);
        });
    }
};
