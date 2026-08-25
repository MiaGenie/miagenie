<?php

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
        Schema::create('genie_version_field_sub_fields', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('field_id')->constrained('genie_version_fields')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('genie_version_field_sub_fields')->onDelete('cascade');
            $table->json('name');
            $table->string('sub_code_name');
            $table->json('description')->nullable();
            $table->tinyInteger('type');
            $table->smallInteger('min_length')->nullable();
            $table->smallInteger('max_length')->nullable();
            $table->smallInteger('min_items')->nullable();
            $table->smallInteger('max_items')->nullable();
            $table->boolean('required')->default(true);
            $table->boolean('editable')->default(true);
            $table->string('pattern')->nullable();
            $table->json('enum_values')->nullable();
            $table->string('icon')->nullable();
            $table->string('class')->nullable();
            $table->string('block')->nullable();
            $table->integer('position');
            $table->timestamps();

            $table->index(['field_id', 'parent_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('genie_version_field_sub_fields');
    }
};
