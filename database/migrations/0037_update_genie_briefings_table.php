<?php

use App\Enums\BriefingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Finishing the briefing is a decision, not a side effect of the answers being filled in. The
     * wizard saves a draft on every question, so the stored content cannot say whether the customer
     * pressed Finish; only this column can.
     *
     * Every existing briefing is backfilled as complete: it was written before the column existed,
     * and a workspace that already has a briefing — or a strategy generated from one — must not be
     * thrown back to the briefing stage.
     */
    public function up(): void
    {
        Schema::table('genie_briefings', function (Blueprint $table) {
            $table->tinyInteger('status')->default(BriefingStatus::DRAFT->value)->after('content');
        });

        DB::table('genie_briefings')->update(['status' => BriefingStatus::COMPLETE->value]);
    }

    public function down(): void
    {
        Schema::table('genie_briefings', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
