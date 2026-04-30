<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('shipment_tracks')) return;

        DB::statement("DO $$
        BEGIN
            IF EXISTS (
                SELECT 1 FROM pg_constraint
                WHERE conname = 'chk_shipment_tracks_status'
            ) THEN
                ALTER TABLE shipment_tracks DROP CONSTRAINT chk_shipment_tracks_status;
            END IF;
        END$$;");

        // Pasang yang baru
        DB::statement("
            ALTER TABLE shipment_tracks
            ADD CONSTRAINT chk_shipment_tracks_status
            CHECK (status IN (
                'pickup','handover','stuffing','delivery_to_port','stacking',
                'unit_loading','onship','vessel_depart','vessel_arrival','unloading',
                'delivery_to_customer','delivered','hold','cancelled'
            ));
        ");
    }

    public function down(): void
    {
        if (!Schema::hasTable('shipment_tracks')) return;
        DB::statement("ALTER TABLE shipment_tracks DROP CONSTRAINT IF EXISTS chk_shipment_tracks_status;");
    }
};
