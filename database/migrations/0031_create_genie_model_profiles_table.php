<?php

use App\Enums\ModelTier;
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
        Schema::create('genie_model_profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('name');
            $table->string('provider');
            $table->string('model_tier')->default(ModelTier::DEFAULT->value);
            $table->string('model')->nullable();
            $table->integer('timeout')->nullable();
            $table->integer('position');
            $table->timestamps();
        });

        Schema::dropIfExists('genie_ai_models');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('genie_ai_models', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('model');
            $table->boolean('json_schema')->default(false);
            $table->boolean('temperature_top_p')->default(false);
            $table->boolean('file_search')->default(false);
            $table->boolean('reasoning_effort')->default(false);
            $table->timestamps();
        });

        Schema::dropIfExists('genie_model_profiles');
    }
};
