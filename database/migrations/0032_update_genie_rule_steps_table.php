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

        Schema::table('genie_rule_steps', function (Blueprint $table) {
            $table->dropColumn(['ai_model', 'vector_id', 'temperature', 'top_p', 'reasoning_effort', 'json_schema']);
            $table->dropForeign('genie_rule_steps_depends_on_option_foreign');
            $table->foreign('depends_on_option')->references('id')->on('genie_version_field_sub_fields')->onDelete('SET NULL');
        });

        Schema::table('genie_rule_steps', function (Blueprint $table) {
            $table->foreignId('model_profile_id')
                ->nullable()
                ->after('instructions')
                ->constrained('genie_model_profiles')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('genie_rule_steps', function (Blueprint $table) {
            $table->json('json_schema')->nullable()->after('response_format');
            $table->string('ai_model')->nullable()->after('instructions');
            $table->integer('vector_id')->nullable()->after('reasoning_effort');
            $table->decimal('temperature', 3, 2)->nullable()->after('json_schema');
            $table->decimal('top_p', 3, 2)->nullable()->after('temperature');
            $table->string('reasoning_effort')->nullable()->after('top_p');
            $table->dropForeign('genie_rule_steps_depends_on_option_foreign');
            $table->foreign('depends_on_option')->references('id')->on('genie_version_field_options')->onDelete('SET NULL');
        });

        Schema::table('genie_rule_steps', function (Blueprint $table) {
            $table->dropConstrainedForeignId('model_profile_id');
        });
    }
};
