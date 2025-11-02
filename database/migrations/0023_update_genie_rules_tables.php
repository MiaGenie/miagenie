<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('genie_rules', function (Blueprint $table) {
            $table->boolean('link_upstream')->default(false)->after('rule_type');
        });

        Schema::table('genie_rule_steps', function (Blueprint $table) {
            $table->boolean('link_upstream')->default(false)->after('rule_sub_type');
            $table->json('output')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('genie_rule_steps', function (Blueprint $table) {
            $table->dropColumn('link_upstream');
            $table->json('output')->change();
        });

        Schema::table('genie_rules', function (Blueprint $table) {
            $table->dropColumn('link_upstream');
        });
    }
};