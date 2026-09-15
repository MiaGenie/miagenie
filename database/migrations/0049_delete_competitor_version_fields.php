<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The "competitors" version-field group (VersionGroupType 2) went with the competitors feature.
 * Its fields have no reader left; their options and sub-fields cascade.
 *
 * Legacy rules (rule types 3 and 4, drafts and pre-posts) are deliberately left alone here and
 * deleted by hand when needed.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('genie_version_fields')->where('group_type', 2)->delete();
    }

    /**
     * Reverse the migrations.
     *
     * The deleted rows are not recoverable.
     */
    public function down(): void
    {
        //
    }
};
