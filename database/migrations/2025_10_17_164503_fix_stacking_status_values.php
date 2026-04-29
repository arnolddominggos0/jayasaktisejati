<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('shipment_tracks')) return;

        DB::statement("UPDATE shipment_tracks SET status = TRIM(status)");
        DB::table('shipment_tracks')
            ->where('status', 'stacking_start')
            ->update(['status' => 'stacking']);
    }

    public function down(): void
    {
        // no-op (jangan balikin ke nilai salah)
    }
};
