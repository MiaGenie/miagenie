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
        Schema::table('mixpost_workspaces', function (Blueprint $table) {

            $table->string('locale')->nullable()->after('limits');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('mixpost_workspaces', function (Blueprint $table) {

            $table->dropColumn('locale');

        });
    }
};