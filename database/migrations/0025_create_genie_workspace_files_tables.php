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

        Schema::create('genie_workspace_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('workspace_id')->constrained('mixpost_workspaces')->onDelete('cascade');
            $table->string('name');
            $table->string('mime_type');
            $table->string('disk');
            $table->string('path');
            $table->unsignedBigInteger('size');
            $table->tinyInteger('type');
            $table->tinyInteger('source');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('genie_run_response_workspace_file', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_response_id')->constrained('genie_run_responses')->onDelete('cascade');
            $table->foreignId('workspace_file_id')->nullable()->constrained('genie_workspace_files')->nullOnDelete();
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
        Schema::dropIfExists('genie_run_response_workspace_file');
        Schema::dropIfExists('genie_workspace_files');
    }
};
