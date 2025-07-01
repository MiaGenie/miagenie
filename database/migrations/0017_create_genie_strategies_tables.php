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
    public function up()
    {
        Schema::create('genie_strategies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('workspace_id')->constrained('mixpost_workspaces')->onDelete('cascade');
            $table->foreignId('run_id')->constrained('genie_runs');
            $table->json('content')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('genie_strategy_reviews', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('strategy_id')->constrained('genie_strategies')->onDelete('cascade');
            $table->foreignId('run_response_id')->nullable()->constrained('genie_run_responses')->nullOnDelete();
            $table->json('original');
            $table->json('edited')->nullable();
            $table->boolean('status')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('genie_strategies');
    }
};
