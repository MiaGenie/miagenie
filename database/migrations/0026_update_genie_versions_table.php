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
        Schema::table('genie_version_fields', function (Blueprint $table) {
            $table->tinyInteger('file_type')->nullable()->after('input_type');
            $table->boolean('is_linkable')->default(false)->after('hidden');
            $table->boolean('display_title')->default(true)->after('is_linkable');
            $table->boolean('display_grouped')->default(false)->after('display_title');
            $table->boolean('display_field_title')->default(false)->after('display_grouped');
            $table->boolean('display_item_title')->default(false)->after('display_field_title');
            $table->json('display_faq_title')->nullable()->after('display_item_title');
            $table->json('display_faq_text')->nullable()->after('display_faq_title');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('genie_version_fields', function (Blueprint $table) {
            $table->dropColumn('file_type');
            $table->dropColumn('is_linkable');
            $table->dropColumn('display_title');
            $table->dropColumn('display_grouped');
//            $table->dropColumn('display_grouped_properties');
            $table->dropColumn('display_field_title');
            $table->dropColumn('display_item_title');
            $table->dropColumn('display_faq_title');
            $table->dropColumn('display_faq_text');
        });
    }
};