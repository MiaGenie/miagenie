<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('genie_rule_steps', function (Blueprint $table) {
            $table->json('instructions')->nullable()->change();
            $table->json('message')->nullable()->change();
            $table->json('json_schema')->nullable()->change();
            $table->json('review_message_user')->nullable()->change();
            $table->json('review_message_system')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('genie_rule_steps', function (Blueprint $table) {
            $table->text('instructions')->change();
            $table->text('json_schema')->change();
            $table->text('message')->change();
            $table->text('review_message_user')->change();
            $table->text('review_message_system')->change();
        });
    }
};