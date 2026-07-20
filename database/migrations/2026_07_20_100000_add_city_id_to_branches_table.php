<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Smart Origin Migration (Office -> Branch).
 *
 * branches previously had no relation to cities at all (only id/code/name —
 * see 2025_08_14_111824_create_branches_table.php). origin_city_id was
 * derived via a fragile string-match against offices.city (a free-text
 * column, no FK — see 2025_08_15_075624_create_offices_table.php). This adds
 * a real foreign key so Branch alone can be the source of truth for Origin,
 * per docs/master-office/SMART-ORIGIN-MIGRATION-BLOCKED-SCHEMA-GAP.md.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (! Schema::hasColumn('branches', 'city_id')) {
                $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            }
        });

        // One-time backfill for the branches that exist today: both
        // ("Jakarta", "Manado") share their name exactly with an active
        // city of the same name, so this is an unambiguous match for 2
        // rows — not a general-purpose lookup mechanism. No string-matching
        // is introduced into application code; this statement only runs
        // once, here, against existing data. Same backfill pattern already
        // used by 2025_09_14_150720_add_city_refs_to_shipments_and_backfill.php.
        DB::statement("
            UPDATE branches b
            SET city_id = c.id
            FROM cities c
            WHERE LOWER(c.name) = LOWER(b.name)
              AND b.city_id IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'city_id')) {
                $table->dropConstrainedForeignId('city_id');
            }
        });
    }
};
