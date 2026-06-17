<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SC.3B.19 — Shipment-Based MP Check
 *
 * Before: BriefingSession keyed by (date, depot_id) — one per depot per day.
 * After : BriefingSession keyed by shipment_id — one per operational shipment.
 *
 * date column is preserved for historical / monitoring queries.
 * Old unique(date, depot_id) is dropped; new unique(shipment_id) added.
 * Postgres permits multiple NULL shipment_id rows (legacy date-based records).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('briefing_sessions', function (Blueprint $table) {
            // Add shipment FK (nullable — existing date-based records have no shipment)
            $table->foreignId('shipment_id')
                ->nullable()
                ->after('appsheet_id')
                ->constrained('shipments')
                ->nullOnDelete();

            // One briefing per shipment (NULLs are excluded from unique check in Postgres)
            $table->unique('shipment_id');
        });

        // Drop the old date+depot unique constraint
        // (In Postgres the generated name is briefing_sessions_date_depot_id_unique)
        Schema::table('briefing_sessions', function (Blueprint $table) {
            try {
                $table->dropUnique(['date', 'depot_id']);
            } catch (\Throwable) {
                // Constraint may not exist on fresh installs — safe to ignore
            }
        });
    }

    public function down(): void
    {
        Schema::table('briefing_sessions', function (Blueprint $table) {
            $table->dropForeign(['shipment_id']);
            $table->dropUnique(['shipment_id']);
            $table->dropColumn('shipment_id');
            $table->unique(['date', 'depot_id']);
        });
    }
};
