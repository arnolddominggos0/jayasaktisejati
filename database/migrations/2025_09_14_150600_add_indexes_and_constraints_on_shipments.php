<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // ... biarkan index/constraint lain yang TIDAK pakai fleet_schedule_id tetap di sini ...

        if (Schema::hasColumn('shipments', 'fleet_schedule_id')) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('CREATE INDEX IF NOT EXISTS shipments_schedule_index ON shipments (fleet_schedule_id)');
            } else {
                Schema::table('shipments', function (Blueprint $table) {
                    $table->index('fleet_schedule_id', 'shipments_schedule_index');
                });
            }

            if (Schema::hasTable('fleet_schedules')) {
                Schema::table('shipments', function (Blueprint $table) {
                    $table->foreign('fleet_schedule_id')
                        ->references('id')->on('fleet_schedules')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('shipments', 'fleet_schedule_id')) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('DROP INDEX IF EXISTS shipments_schedule_index');
            } else {
                Schema::table('shipments', function (Blueprint $table) {
                    $table->dropIndex('shipments_schedule_index');
                });
            }

            // Nama konvensi Laravel: shipments_fleet_schedule_id_foreign
            Schema::table('shipments', function (Blueprint $table) {
                try { $table->dropForeign(['fleet_schedule_id']); } catch (\Throwable $e) { /* diam */ }
            });
        }
    }
};
