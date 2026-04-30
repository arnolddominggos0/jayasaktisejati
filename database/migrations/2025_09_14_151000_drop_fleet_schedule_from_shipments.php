<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('shipments')) return;

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE shipments DROP CONSTRAINT IF EXISTS shipments_fleet_schedule_id_foreign');
            DB::statement('DROP INDEX IF EXISTS shipments_schedule_index');
        }

        if (Schema::hasColumn('shipments', 'fleet_schedule_id')) {
            Schema::table('shipments', function (Blueprint $table) {
                try { $table->dropForeign(['fleet_schedule_id']); } catch (\Throwable $e) {}
                try { $table->dropIndex('shipments_schedule_index'); } catch (\Throwable $e) {}
                $table->dropColumn('fleet_schedule_id');
            });
        }
    }

    public function down(): void
    {
        // Optional: balikin kolom kalau kamu bener-bener butuh rollback “utuh”
        if (Schema::hasTable('shipments') && ! Schema::hasColumn('shipments', 'fleet_schedule_id')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->unsignedBigInteger('fleet_schedule_id')->nullable();
            });

            if (DB::getDriverName() === 'pgsql') {
                // jangan otomatis pasang FK lagi kalau memang sudah ditinggalkan
                // DB::statement('ALTER TABLE shipments ADD CONSTRAINT shipments_fleet_schedule_id_foreign ...');
            }
        }
    }
};
