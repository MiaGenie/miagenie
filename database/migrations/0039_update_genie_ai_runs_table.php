<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What the run was asked for — the format, content pillar, funnel stage and count of an ideas
     * run, later the idea a posts run writes from. A strategy run has none.
     */
    public function up(): void
    {
        Schema::table('genie_ai_runs', function (Blueprint $table) {
            $table->json('parameters')->nullable()->after('strategy_id');
        });
    }

    public function down(): void
    {
        Schema::table('genie_ai_runs', function (Blueprint $table) {
            $table->dropColumn('parameters');
        });
    }
};
