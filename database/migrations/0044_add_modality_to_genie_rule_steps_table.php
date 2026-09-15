<?php

use App\Enums\Modality;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A rule step now says what it produces: text, or an image generated from its message.
     *
     * Every existing step is text, so the TEXT default leaves them unchanged; the runner copies
     * the value onto each run step it opens and branches on it.
     */
    public function up(): void
    {
        Schema::table('genie_rule_steps', function (Blueprint $table) {
            $table->tinyInteger('modality')->default(Modality::TEXT->value)->after('rule_sub_type');
        });
    }

    public function down(): void
    {
        Schema::table('genie_rule_steps', function (Blueprint $table) {
            $table->dropColumn('modality');
        });
    }
};
