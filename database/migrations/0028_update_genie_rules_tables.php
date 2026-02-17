<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('genie_rules', function (Blueprint $table) {
            $table->dropForeign(['version_id']);
            $table->foreign('version_id')
                ->references('id')->on('genie_versions')
                ->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('genie_rules', function (Blueprint $table) {
            $table->dropForeign(['version_id']);
            $table->foreign('version_id')
                ->references('id')->on('genie_versions');
        });
    }
};
