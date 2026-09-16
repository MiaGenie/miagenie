<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `is_identifier` marked the TEXT input naming a competitor in the "competitors" version-field group.
 * That group went with the competitors feature (migration 0049), so no group can set the flag any more.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('genie_version_fields', function (Blueprint $table) {
            $table->dropColumn('is_identifier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('genie_version_fields', function (Blueprint $table) {
            $table->boolean('is_identifier')->default(false)->after('required');
        });
    }
};
