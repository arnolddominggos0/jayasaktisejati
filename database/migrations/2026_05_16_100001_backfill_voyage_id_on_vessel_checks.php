<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('vessel_checks', 'voyage_id')) {
            return;
        }

        DB::statement("
            UPDATE vessel_checks vc
            SET voyage_id = ss.voyage_id
            FROM shipping_schedules ss
            WHERE vc.shipping_schedule_id = ss.id
              AND vc.voyage_id IS NULL
              AND ss.voyage_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        // No-op: backfill is idempotent and safe to re-run
    }
};
