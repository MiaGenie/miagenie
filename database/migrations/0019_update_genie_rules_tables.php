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

            $table->bigInteger('depends_on_field')->unsigned()->nullable()->after('optional');
            $table->foreign('depends_on_field')->references('id')->on('genie_version_fields')->onDelete('SET NULL');
            $table->bigInteger('depends_on_option')->unsigned()->nullable()->after('depends_on_field');
            $table->foreign('depends_on_option')->references('id')->on('genie_version_field_options')->onDelete('SET NULL');

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

            $table->dropForeign(['depends_on_option']);
            $table->dropColumn('depends_on_option');
            $table->dropForeign(['depends_on_field']);
            $table->dropColumn('depends_on_field');

        });
    }
};