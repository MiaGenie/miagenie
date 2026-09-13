<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A step that generates content for one post format (an ideas step now, a posts step later)
     * names that format here, so a run can pick the steps that match what was asked for.
     */
    public function up(): void
    {
        Schema::table('genie_rule_steps', function (Blueprint $table) {
            $table->tinyInteger('post_format')->nullable()->after('rule_sub_type');
        });
    }

    public function down(): void
    {
        Schema::table('genie_rule_steps', function (Blueprint $table) {
            $table->dropColumn('post_format');
        });
    }
};
