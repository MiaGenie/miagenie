<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The social providers an idea will generate a post for, as a list of Mixpost provider keys.
     * What is on offer is the intersection of the providers that accept the idea's format and the
     * providers the workspace has an account for; the posts run reads the list back.
     */
    public function up(): void
    {
        Schema::table('genie_ideas', function (Blueprint $table) {
            $table->json('publish_to')->nullable()->after('images');
        });
    }

    public function down(): void
    {
        Schema::table('genie_ideas', function (Blueprint $table) {
            $table->dropColumn('publish_to');
        });
    }
};
