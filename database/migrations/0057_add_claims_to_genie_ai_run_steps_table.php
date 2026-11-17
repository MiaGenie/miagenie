<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Makes Runner::claim() hold under a race and lets a job take back its own step.
 *
 * `claimed_by` is the queue job uuid that set the step RUNNING: a job that threw after claiming
 * comes back with the same uuid and may claim the step again, where any other job is refused until
 * the claim expires. The unique `(run_id, step_id)` index turns the first claim of a step, which
 * creates its row, into one that only one of two racing jobs can win.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('genie_ai_run_steps', function (Blueprint $table) {
            $table->char('claimed_by', 36)->nullable()->after('status');

            $table->unique(['run_id', 'step_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('genie_ai_run_steps', function (Blueprint $table) {
            $table->dropUnique(['run_id', 'step_id']);
            $table->dropColumn('claimed_by');
        });
    }
};
