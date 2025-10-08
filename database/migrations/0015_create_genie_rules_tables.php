<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('genie_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('version_id')->constrained('genie_versions');
            $table->tinyInteger('rule_type');
            $table->string('name');
            $table->text('description')->nullable();
            $table->tinyInteger('status');
            $table->timestamps();
        });


        Schema::create('genie_rule_steps', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('rule_id')->constrained('genie_rules')->onDelete('cascade');
            $table->tinyInteger('rule_sub_type')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('instructions');
            $table->string('ai_model');
            $table->string('response_format')->nullable();
            $table->text('json_schema')->nullable();
            $table->string('temperature')->nullable();
            $table->string('top_p')->nullable();
            $table->string('reasoning_effort')->nullable();
            $table->integer('vector_id')->nullable();
            $table->text('message');
            $table->string('output');
            $table->boolean('requires_review');
            $table->text('review_message_user')->nullable();
            $table->text('review_message_system')->nullable();
            $table->boolean('optional');
            $table->integer('position');
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
        Schema::dropIfExists('genie_rule_steps');
        Schema::dropIfExists('genie_rules');
    }
};
