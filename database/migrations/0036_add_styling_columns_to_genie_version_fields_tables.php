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
        Schema::table('genie_version_fields', function (Blueprint $table) {
            $table->string('class')->nullable()->after('display_faq_text');
            $table->string('block')->nullable()->after('class');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('genie_version_fields', function (Blueprint $table) {
            $table->dropColumn(['class', 'block']);
        });
    }
};
