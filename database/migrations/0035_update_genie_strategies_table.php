<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A strategy used to be reachable only from the legacy run that produced it. The new run
     * tables own that link from the other side (`genie_ai_runs.strategy_id`), so a strategy
     * created by the new pipeline has no legacy run to point at.
     *
     * The old pipeline keeps setting the column while it is still in use.
     */
    public function up(): void
    {
        Schema::table('genie_strategies', function (Blueprint $table) {
            $table->dropColumn(['run_id']);
        });
    }

    public function down(): void
    {
        Schema::table('genie_strategies', function (Blueprint $table) {
            $table->foreignId('run_id')->constrained('genie_runs');
        });
    }
};
