<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * `enum_values` becomes translatable: the plain list every row holds is re-keyed under the
     * default locale, which is the language the admin wrote it in.
     */
    public function up(): void
    {
        $locale = (string) config('mixpost.default_locale');

        DB::table('genie_version_field_sub_fields')
            ->whereNotNull('enum_values')
            ->orderBy('id')
            ->each(function (object $row) use ($locale) {
                $values = json_decode($row->enum_values, true);

                if (! is_array($values) || ! array_is_list($values)) {
                    return;
                }

                DB::table('genie_version_field_sub_fields')
                    ->where('id', $row->id)
                    ->update(['enum_values' => json_encode([$locale => $values], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
            });
    }

    public function down(): void
    {
        $locale = (string) config('mixpost.default_locale');

        DB::table('genie_version_field_sub_fields')
            ->whereNotNull('enum_values')
            ->orderBy('id')
            ->each(function (object $row) use ($locale) {
                $values = json_decode($row->enum_values, true);

                if (! is_array($values) || array_is_list($values)) {
                    return;
                }

                $source = $values[$locale] ?? null;

                DB::table('genie_version_field_sub_fields')
                    ->where('id', $row->id)
                    ->update(['enum_values' => $source === null ? null : json_encode($source, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
            });
    }
};
