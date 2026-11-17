<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a strategy field carry an icon picked from Material Design Icons. `icon` is the icon's name
 * (`mdi:rocket-launch`) and `icon_path` its SVG path, stored with it so the workspace pages draw the
 * icon without loading the icon set.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('genie_version_fields', function (Blueprint $table) {
            $table->string('icon')->nullable()->after('block');
            $table->text('icon_path')->nullable()->after('icon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('genie_version_fields', function (Blueprint $table) {
            $table->dropColumn(['icon', 'icon_path']);
        });
    }
};
