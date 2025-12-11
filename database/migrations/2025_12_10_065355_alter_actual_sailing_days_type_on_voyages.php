<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('voyages', 'actual_sailing_days')) {
            DB::statement("ALTER TABLE voyages ADD COLUMN actual_sailing_days numeric(6,2) DEFAULT 0.00");
            return;
        }

        DB::statement("ALTER TABLE voyages ALTER COLUMN actual_sailing_days TYPE numeric(6,2) USING ROUND(actual_sailing_days::numeric, 2)");
    }


    public function down(): void
    {
        if (! Schema::hasColumn('voyages', 'actual_sailing_days')) {
            return;
        }

        DB::statement("ALTER TABLE voyages ALTER COLUMN actual_sailing_days TYPE integer USING ROUND(actual_sailing_days)::integer");
    }
};
