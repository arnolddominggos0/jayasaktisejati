<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE shipment_tracks DROP CONSTRAINT IF EXISTS chk_shipment_tracks_status');

        DB::statement("
            ALTER TABLE shipment_tracks
            ADD CONSTRAINT chk_shipment_tracks_status
            CHECK (status_normalized IN (
                10,   -- pickup
                20,   -- handover
                30,   -- stuffing
                40,   -- delivery_to_port
                50,   -- stacking
                60,   -- unit_loading
                70,   -- onship
                80,   -- vessel_depart
                90,   -- vessel_arrival
                100,  -- unloading
                105,  -- handover_trucking 🔥
                110,  -- delivery_to_customer
                120,  -- delivered
                900,  -- hold
                999   -- cancelled
            ))
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE shipment_tracks DROP CONSTRAINT IF EXISTS chk_shipment_tracks_status');

        DB::statement("
            ALTER TABLE shipment_tracks
            ADD CONSTRAINT chk_shipment_tracks_status
            CHECK (status_normalized IN (
                10,
                20,
                30,
                40,
                50,
                60,
                70,
                80,
                90,
                100,
                110,
                120,
                900,
                999
            ))
        ");
    }
};