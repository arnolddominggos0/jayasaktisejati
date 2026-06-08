<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── vessel_checks ─────────────────────────────────────────────────
        Schema::table('vessel_checks', function (Blueprint $table) {
            $table->dropForeign(['shipping_schedule_id']);
            $table->dropUnique('vessel_checks_shipping_schedule_id_check_date_unique');
            $table->unsignedBigInteger('shipping_schedule_id')->nullable()->change();
            $table->foreign('shipping_schedule_id')
                ->references('id')->on('shipping_schedules')
                ->nullOnDelete();
        });

        // UNIQUE(voyage_id, check_date) — partial index skips NULLs
        DB::statement("
            DO \$\$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_indexes
                    WHERE tablename = 'vessel_checks'
                    AND   indexname = 'vessel_checks_voyage_id_check_date_unique'
                ) THEN
                    CREATE UNIQUE INDEX vessel_checks_voyage_id_check_date_unique
                    ON vessel_checks(voyage_id, check_date)
                    WHERE voyage_id IS NOT NULL;
                END IF;
            END\$\$;
        ");

        // ── vessel_check_cases ────────────────────────────────────────────
        Schema::table('vessel_check_cases', function (Blueprint $table) {
            $table->dropForeign(['shipping_schedule_id']);
            $table->unsignedBigInteger('shipping_schedule_id')->nullable()->change();
            $table->foreign('shipping_schedule_id')
                ->references('id')->on('shipping_schedules')
                ->nullOnDelete();
        });

        // ── tam_shipments ─────────────────────────────────────────────────
        Schema::table('tam_shipments', function (Blueprint $table) {
            $table->dropForeign(['shipping_schedule_id']);
            $table->unsignedBigInteger('shipping_schedule_id')->nullable()->change();
            $table->foreign('shipping_schedule_id')
                ->references('id')->on('shipping_schedules')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Restore vessel_checks
        Schema::table('vessel_checks', function (Blueprint $table) {
            $table->dropForeign(['shipping_schedule_id']);
            $table->unsignedBigInteger('shipping_schedule_id')->nullable(false)->change();
            $table->foreign('shipping_schedule_id')
                ->references('id')->on('shipping_schedules')
                ->cascadeOnDelete();
            $table->unique(['shipping_schedule_id', 'check_date']);
        });

        DB::statement("DROP INDEX IF EXISTS vessel_checks_voyage_id_check_date_unique");

        // Restore vessel_check_cases
        Schema::table('vessel_check_cases', function (Blueprint $table) {
            $table->dropForeign(['shipping_schedule_id']);
            $table->unsignedBigInteger('shipping_schedule_id')->nullable(false)->change();
            $table->foreign('shipping_schedule_id')
                ->references('id')->on('shipping_schedules')
                ->cascadeOnDelete();
        });

        // Restore tam_shipments
        Schema::table('tam_shipments', function (Blueprint $table) {
            $table->dropForeign(['shipping_schedule_id']);
            $table->unsignedBigInteger('shipping_schedule_id')->nullable(false)->change();
            $table->foreign('shipping_schedule_id')
                ->references('id')->on('shipping_schedules')
                ->cascadeOnDelete();
        });
    }
};
