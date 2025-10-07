<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('genie_ai_models', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('model');
            $table->boolean('json_schema');
            $table->boolean('temperature_top_p');
            $table->boolean('file_search');
            $table->boolean('reasoning_effort');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('genie_ai_models');
    }
};
