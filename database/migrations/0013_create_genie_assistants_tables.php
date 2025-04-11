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

        Schema::create('genie_assistants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->tinyInteger('assistant_type');
            $table->text('description')->nullable();
            $table->text('instructions');
            $table->string('model');
            $table->integer('vector_id')->nullable();
            $table->string('response_format')->nullable();
            $table->text('json_schema')->nullable();
            $table->string('temperature')->nullable();
            $table->string('top_p')->nullable();
            $table->tinyInteger('status');
            $table->string('assistant_provider_id')->nullable();
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
        Schema::dropIfExists('genie_assistants');
    }
};
