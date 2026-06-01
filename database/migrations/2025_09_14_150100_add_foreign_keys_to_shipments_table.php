<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shipments')) return;

        Schema::table('shipments', function (Blueprint $t) {
            DB::statement('ALTER TABLE shipments DROP CONSTRAINT IF EXISTS shipments_schedule_id_foreign');
            DB::statement('ALTER TABLE shipments DROP CONSTRAINT IF EXISTS shipments_driver_id_foreign');

            if (Schema::hasTable('fleet_schedules') && Schema::hasColumn('shipments', 'fleet_schedule_id')) {
                $t->foreign('fleet_schedule_id')
                  ->references('id')->on('fleet_schedules')
                  ->nullOnDelete();
            }

            if (Schema::hasTable('drivers') && Schema::hasColumn('shipments', 'driver_id')) {
                $t->foreign('driver_id')
                  ->references('id')->on('drivers')
                  ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('shipments')) return;

        DB::statement('ALTER TABLE shipments DROP CONSTRAINT IF EXISTS shipments_schedule_id_foreign');
        DB::statement('ALTER TABLE shipments DROP CONSTRAINT IF EXISTS shipments_driver_id_foreign');
    }
};
