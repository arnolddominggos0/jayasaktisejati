<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('shipment_tracks') || !Schema::hasColumn('shipment_tracks', 'status')) {
            return;
        }

        DB::statement("UPDATE shipment_tracks SET status = TRIM(status)");

        DB::statement("
            UPDATE shipment_tracks
            SET status = CASE
                WHEN status IN ('stuffing_start','stuffing_briefing','stuffing_done')
                    THEN 'stuffing'
                WHEN status = 'port_in'
                    THEN 'delivery_to_port'
                WHEN status = 'vessel_atd'
                    THEN 'vessel_depart'
                WHEN status = 'vessel_ata'
                    THEN 'vessel_arrival'
                WHEN status = 'stripping_start'
                    THEN 'unloading'
                ELSE status
            END
            WHERE status IN (
                'stuffing_start','stuffing_briefing','stuffing_done',
                'port_in','vessel_atd','vessel_ata','stripping_start'
            )
        ");
    }

    public function down(): void
    {
    }
};
