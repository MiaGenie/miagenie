<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A log entry is one model call; this says which run step it was made for.
     *
     * A run step stays one row per rule step attempted in a run (the loop's identity), so a step
     * that calls the model several times — an image step with one call per brief, a regeneration
     * of one of its slots — is read call by call here. Older entries keep null.
     */
    public function up(): void
    {
        Schema::table('genie_logs', function (Blueprint $table) {
            $table->foreignId('run_step_id')->nullable()->after('action')
                ->constrained('genie_ai_run_steps')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('genie_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('run_step_id');
        });
    }
};
