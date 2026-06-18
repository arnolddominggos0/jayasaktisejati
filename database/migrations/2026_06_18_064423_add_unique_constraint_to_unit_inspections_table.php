<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Promotes the (unit_id, stage) btree index to a UNIQUE constraint.
 *
 * PRE-FLIGHT: Detects and ABORTS if duplicate (unit_id, stage) pairs exist.
 * The caller must resolve duplicates manually before running this migration.
 *
 * Drops the existing non-unique composite index first, then adds the unique
 * constraint (which implicitly creates a new unique index in Postgres).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Safety check: abort if duplicates exist ───────────────────────────
        $dupes = DB::select('
            SELECT unit_id, stage, COUNT(*) AS cnt
            FROM unit_inspections
            GROUP BY unit_id, stage
            HAVING COUNT(*) > 1
        ');

        if (! empty($dupes)) {
            $detail = collect($dupes)
                ->map(fn ($r) => "  unit_id={$r->unit_id} stage={$r->stage} count={$r->cnt}")
                ->join("\n");

            throw new \RuntimeException(
                "Cannot add UNIQUE constraint: duplicate (unit_id, stage) pairs found:\n{$detail}\n" .
                'Resolve duplicates before running this migration.'
            );
        }

        Schema::table('unit_inspections', function (Blueprint $table) {
            // Drop the existing non-unique composite index.
            // Name format: {table}_{column1}_{column2}_index (Laravel convention).
            $table->dropIndex(['unit_id', 'stage']);

            // Add unique constraint (implicitly creates a unique index in Postgres).
            $table->unique(['unit_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::table('unit_inspections', function (Blueprint $table) {
            $table->dropUnique(['unit_id', 'stage']);
            $table->index(['unit_id', 'stage']);
        });
    }
};
