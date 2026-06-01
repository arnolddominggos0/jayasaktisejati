<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS idx_voyages_vessel_plan_item_id ON voyages (vessel_plan_item_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_voyages_etd ON voyages (etd)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_voyages_eta ON voyages (eta)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_voyages_vessel_plan_item_id');
        DB::statement('DROP INDEX IF EXISTS idx_voyages_etd');
        DB::statement('DROP INDEX IF EXISTS idx_voyages_eta');
    }
};
