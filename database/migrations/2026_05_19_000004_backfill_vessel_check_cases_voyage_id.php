<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1.5: Backfill vessel_check_cases.voyage_id from shipping_schedule
        // For each vessel_check_case, find voyage_id via its shipping_schedule.
        // Safe: only updates NULL values, never overwrites existing data.

        if (Schema::hasColumn('vessel_check_cases', 'voyage_id')) {
            DB::statement("
                UPDATE vessel_check_cases
                SET voyage_id = (
                    SELECT voyage_id
                    FROM shipping_schedules
                    WHERE shipping_schedules.id = vessel_check_cases.shipping_schedule_id
                )
                WHERE voyage_id IS NULL
                  AND shipping_schedule_id IS NOT NULL
                  AND EXISTS (
                      SELECT 1 FROM shipping_schedules
                      WHERE shipping_schedules.id = vessel_check_cases.shipping_schedule_id
                        AND shipping_schedules.voyage_id IS NOT NULL
                  )
            ");
        }
    }

    public function down(): void
    {
        // Cannot reliably reverse data backfill — mark as no-op
        // Rollback is data restore from backup, not migration
    }
};