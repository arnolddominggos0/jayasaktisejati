<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('shipping_schedules', 'actual_sailing_days')) {
            DB::statement("ALTER TABLE shipping_schedules ADD COLUMN actual_sailing_days numeric(6,2) DEFAULT 0.00");
            return;
        }

        DB::statement("ALTER TABLE shipping_schedules ALTER COLUMN actual_sailing_days TYPE numeric(6,2) USING ROUND(actual_sailing_days::numeric, 2)");
    }

    public function down(): void
    {
        if (! Schema::hasColumn('shipping_schedules', 'actual_sailing_days')) {
            return;
        }

        DB::statement("ALTER TABLE shipping_schedules ALTER COLUMN actual_sailing_days TYPE smallint USING ROUND(actual_sailing_days)::smallint");
    }
};
