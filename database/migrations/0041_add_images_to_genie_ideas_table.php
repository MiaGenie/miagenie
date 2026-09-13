<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One production brief per image, in slide order, for the formats that are a set of images:
     * a simple post has one, a carousel several. The posts run generates one image per entry, which
     * a single production_instructions text could not be split into.
     */
    public function up(): void
    {
        Schema::table('genie_ideas', function (Blueprint $table) {
            $table->json('images')->nullable()->after('production_instructions');
        });
    }

    public function down(): void
    {
        Schema::table('genie_ideas', function (Blueprint $table) {
            $table->dropColumn('images');
        });
    }
};
