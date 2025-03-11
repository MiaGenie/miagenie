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
        Schema::create('genie_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->tinyInteger('status');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('genie_workspaces_versions', function (Blueprint $table) {
            $table->foreignId('workspace_id')->primary()->constrained('mixpost_workspaces')->onDelete('cascade');
            $table->foreignId('version_id')->constrained('genie_versions')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('genie_version_fields', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('version_id')->constrained('genie_versions')->onDelete('cascade');
            $table->tinyInteger('group_type');
            $table->json('name');
            $table->string('code_name');
            $table->json('description')->nullable();
            $table->json('sub_description')->nullable();
            $table->tinyInteger('field_type');
            $table->tinyInteger('input_type')->nullable();
            $table->smallInteger('min_length')->nullable();
            $table->smallInteger('max_length')->nullable();
            $table->smallInteger('min_value')->nullable();
            $table->smallInteger('max_value')->nullable();
            $table->smallInteger('step')->nullable();
            $table->tinyInteger('rows')->nullable();
            $table->boolean('genie_required')->default(true);
            $table->boolean('required')->default(false);
            $table->boolean('is_identifier')->default(false);
            $table->integer('position');
            $table->timestamps();
        });

        Schema::create('genie_version_field_options', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('field_id')->constrained('genie_version_fields')->onDelete('cascade');
            $table->json('name');
            $table->string('code_name');
            $table->boolean('checked')->default(false);
            $table->integer('group');
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
        Schema::dropIfExists('genie_workspaces_versions');
        Schema::dropIfExists('genie_version_field_options');
        Schema::dropIfExists('genie_version_fields');
        Schema::dropIfExists('genie_versions');
    }
};
