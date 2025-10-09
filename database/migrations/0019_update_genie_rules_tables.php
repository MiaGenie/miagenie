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
        Schema::table('genie_rule_steps', function (Blueprint $table) {

            $table->bigInteger('depends_on')->unsigned()->nullable()->after('optional');
            $table->foreign('depends_on')->references('id')->on('genie_version_fields')->onDelete('SET NULL');

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

            $table->dropForeign(['depends_on']);
            $table->dropColumn('depends_on');

        });
    }
};