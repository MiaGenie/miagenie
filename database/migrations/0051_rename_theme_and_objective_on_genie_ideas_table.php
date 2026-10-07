<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An idea's `theme` is its title (the name the generator already asks the model for), and its
 * `objective` is its goal — "objective" was a slip for the Portuguese "objetivo".
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('genie_ideas', function (Blueprint $table) {
            $table->renameColumn('theme', 'title');
            $table->renameColumn('objective', 'goal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('genie_ideas', function (Blueprint $table) {
            $table->renameColumn('title', 'theme');
            $table->renameColumn('goal', 'objective');
        });
    }
};
