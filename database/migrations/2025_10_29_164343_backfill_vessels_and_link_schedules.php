<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Asumsikan masih ada kolom legacy `vessel_name` di shipping_schedules.
        // Jika beda, sesuaikan nama kolomnya.

        // 1) Insert vessels unik per (shipping_line_id, lower(vessel_name))
        DB::statement(<<<'SQL'
            INSERT INTO vessels (shipping_line_id, name, created_at, updated_at)
            SELECT DISTINCT shipping_line_id,
                   TRIM(vessel_name) AS name,
                   NOW(), NOW()
            FROM shipping_schedules
            WHERE vessel_name IS NOT NULL
              AND vessel_name <> ''
              AND NOT EXISTS (
                  SELECT 1 FROM vessels v
                  WHERE v.shipping_line_id = shipping_schedules.shipping_line_id
                    AND LOWER(v.name) = LOWER(TRIM(shipping_schedules.vessel_name))
              )
        SQL);

        // 2) Link schedules ke vessels berdasarkan nama dan shipping line
        DB::statement(<<<'SQL'
            UPDATE shipping_schedules s
            SET vessel_id = v.id
            FROM vessels v
            WHERE v.shipping_line_id = s.shipping_line_id
              AND LOWER(v.name) = LOWER(TRIM(s.vessel_name))
        SQL);
    }

    public function down(): void
    {
        // Tidak rollback agar histori tetap aman
    }
};
