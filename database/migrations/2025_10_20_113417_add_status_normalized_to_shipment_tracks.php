<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipment_tracks', function (Blueprint $table) {
            if (!Schema::hasColumn('shipment_tracks', 'status_normalized')) {
                $table->smallInteger('status_normalized')->default(0)->after('status');
                $table->index(['shipment_id', 'status_normalized'], 'shipment_tracks_shipment_id_status_norm_idx');
            }
        });

        DB::statement("
            UPDATE shipment_tracks
            SET status_normalized = CASE status
                WHEN 'pickup'             THEN 10
                WHEN 'handover'           THEN 20
                WHEN 'stuffing'           THEN 30
                WHEN 'delivery_to_port'   THEN 40
                WHEN 'stacking'           THEN 50
                WHEN 'unit_loading'       THEN 60
                WHEN 'onship'             THEN 70
                WHEN 'vessel_depart'      THEN 80
                WHEN 'vessel_arrival'     THEN 90
                WHEN 'unloading'          THEN 100
                WHEN 'delivery_to_customer' THEN 110
                WHEN 'delivered'          THEN 120
                WHEN 'hold'               THEN 900
                WHEN 'cancelled'          THEN 999
                ELSE 0
            END
        ");
    }

    public function down(): void
    {
        Schema::table('shipment_tracks', function (Blueprint $table) {
            if (Schema::hasColumn('shipment_tracks', 'status_normalized')) {
                $table->dropIndex('shipment_tracks_shipment_id_status_norm_idx');
                $table->dropColumn('status_normalized');
            }
        });
    }
};
