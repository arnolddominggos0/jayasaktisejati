<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Performance indexes for the Monitoring Table query layer (Sprint 6.2C).
 *
 * Rationale for each index:
 *
 * shipments:
 *   customer_id           — route filter: whereIn(customer_id, $tamIds)
 *   mode                  — mode filter: where(mode, 'sea'|'land')
 *   (mode, voyage_id)     — missing_voyage exception: mode='sea' AND voyage_id IS NULL
 *   eta                   — delay exception/sort: eta < NOW()
 *   requested_at          — sort ORDER BY: COALESCE(requested_at, created_at) DESC
 *
 * shipment_tracks:
 *   (shipment_id, id DESC) INCLUDE (status, tracked_at)
 *     — DISTINCT ON (shipment_id) ORDER BY shipment_id, id DESC used in
 *       LatestTrackSubquery. Eliminates Incremental Sort; enables index-only scan.
 */
return new class extends Migration
{
    public function up(): void
    {
        $stmts = [
            'CREATE INDEX IF NOT EXISTS shipments_customer_id_idx
                ON shipments (customer_id)',

            'CREATE INDEX IF NOT EXISTS shipments_mode_idx
                ON shipments (mode)',

            // Partial index — only sea shipments without voyage cover the missing_voyage exception.
            "CREATE INDEX IF NOT EXISTS shipments_sea_missing_voyage_idx
                ON shipments (id)
                WHERE mode = 'sea' AND voyage_id IS NULL",

            'CREATE INDEX IF NOT EXISTS shipments_eta_idx
                ON shipments (eta)
                WHERE eta IS NOT NULL',

            'CREATE INDEX IF NOT EXISTS shipments_requested_at_idx
                ON shipments (requested_at DESC NULLS LAST)',

            // Covering index for LatestTrackSubquery DISTINCT ON pattern.
            'CREATE INDEX IF NOT EXISTS shipment_tracks_shipment_id_id_desc_idx
                ON shipment_tracks (shipment_id, id DESC)
                INCLUDE (status, tracked_at)',
        ];

        foreach ($stmts as $sql) {
            DB::statement($sql);
        }
    }

    public function down(): void
    {
        $drops = [
            'shipments_customer_id_idx',
            'shipments_mode_idx',
            'shipments_sea_missing_voyage_idx',
            'shipments_eta_idx',
            'shipments_requested_at_idx',
            'shipment_tracks_shipment_id_id_desc_idx',
        ];

        foreach ($drops as $idx) {
            DB::statement("DROP INDEX IF EXISTS {$idx}");
        }
    }
};
