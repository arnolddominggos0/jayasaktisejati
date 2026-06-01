<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('shipment_tracks')->whereIn('status', [
            'stuffing_start',
            'stuffing_briefing',
            'stuffing_done',
        ])->update(['status' => 'stuffing']);

        DB::table('shipment_tracks')->where('status', 'port_in')->update(['status' => 'delivery_to_port']);

        DB::table('shipment_tracks')->where('status', 'vessel_atd')->update(['status' => 'vessel_depart']);
        DB::table('shipment_tracks')->where('status', 'vessel_ata')->update(['status' => 'vessel_arrival']);

        DB::table('shipment_tracks')->where('status', 'stripping_start')->update(['status' => 'unloading']);
    }

    public function down(): void {}
};
