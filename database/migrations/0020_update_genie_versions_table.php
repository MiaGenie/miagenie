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
        Schema::table('genie_version_fields', function (Blueprint $table) {
            $table->boolean('hidden')->default(false)->after('is_identifier');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('genie_version_fields', function (Blueprint $table) {
            $table->dropColumn('hidden');
        });
    }
};