<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The assistants feature used OpenAI's Assistants v2 API, which the Laravel AI SDK has no
 * equivalent for. It was never reachable from the run pipeline — no step or rule referenced
 * an assistant — so it is removed rather than ported.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('genie_assistants');
    }

    /**
     * Reverse the migrations.
     *
     * Recreates the table structure only; the rows are not recoverable.
     */
    public function down(): void
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
            $table->string('reasoning_effort')->nullable();
            $table->tinyInteger('status');
            $table->string('assistant_provider_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }
};
