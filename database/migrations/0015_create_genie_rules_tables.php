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
            $table->foreignId('assistant_id')->constrained('genie_assistants');
            $table->text('message');
            $table->string('output');
            $table->boolean('requires_review');
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
