<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a strategy step search the web while it answers (market and competitor research). The search
 * is the provider's own tool: `web_search_max` caps how many searches one call may make and
 * `web_search_domains` restricts results to a list of domains, each for the providers that honour it.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('genie_rule_steps', function (Blueprint $table) {
            $table->boolean('web_search')->default(false)->after('modality');
            $table->unsignedTinyInteger('web_search_max')->nullable()->after('web_search');
            $table->json('web_search_domains')->nullable()->after('web_search_max');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('genie_rule_steps', function (Blueprint $table) {
            $table->dropColumn(['web_search', 'web_search_max', 'web_search_domains']);
        });
    }
};
