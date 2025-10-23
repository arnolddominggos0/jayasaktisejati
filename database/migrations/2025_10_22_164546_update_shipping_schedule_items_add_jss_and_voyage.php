<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_schedule_items', function (Blueprint $table) {
            if (!Schema::hasColumn('shipping_schedule_items', 'jss')) {
                $table->string('jss', 100)->nullable()->after('voyage_no');
                $table->index('jss', 'ssi_jss_idx');
            }

            if (!Schema::hasColumn('shipping_schedule_items', 'voyage_id')) {
                $table->foreignId('voyage_id')
                    ->nullable()
                    ->constrained('voyages')
                    ->nullOnDelete();
            }

            if (
                Schema::hasColumn('shipping_schedule_items', 'vessel_id')
                && Schema::hasColumn('shipping_schedule_items', 'voyage_no')
            ) {
                $table->index(['vessel_id', 'voyage_no'], 'ssi_vessel_voyno_idx');
            }

            if (
                Schema::hasColumn('shipping_schedule_items', 'pol_id')
                && Schema::hasColumn('shipping_schedule_items', 'pod_id')
            ) {
                $table->index(['pol_id', 'pod_id'], 'ssi_pol_pod_idx');
            }

            if (
                Schema::hasColumn('shipping_schedule_items', 'etd')
                && Schema::hasColumn('shipping_schedule_items', 'eta')
            ) {
                $table->index(['etd', 'eta'], 'ssi_etd_eta_idx');
            }
        });

        DB::statement("
    UPDATE shipping_schedule_items
    SET jss = COALESCE(jss, extra->>'jss')
    WHERE jss IS NULL AND extra IS NOT NULL
");

        DB::statement("
    UPDATE shipping_schedule_items
    SET extra = extra - 'jss'
    WHERE extra IS NOT NULL AND jsonb_exists((extra)::jsonb, 'jss')
");
    }

    public function down(): void
    {
        DB::statement("
            UPDATE shipping_schedule_items
            SET extra = jsonb_set(
                COALESCE(extra, '{}'::jsonb),
                '{jss}',
                to_jsonb(jss)
            )
            WHERE jss IS NOT NULL
        ");

        Schema::table('shipping_schedule_items', function (Blueprint $table) {
            if (Schema::hasColumn('shipping_schedule_items', 'jss')) {
                try {
                    $table->dropIndex('ssi_jss_idx');
                } catch (\Throwable $e) {
                }
                $table->dropColumn('jss');
            }

            if (Schema::hasColumn('shipping_schedule_items', 'voyage_id')) {
                $table->dropConstrainedForeignId('voyage_id');
            }

            try {
                $table->dropIndex('ssi_vessel_voyno_idx');
            } catch (\Throwable $e) {
            }
            try {
                $table->dropIndex('ssi_pol_pod_idx');
            } catch (\Throwable $e) {
            }
            try {
                $table->dropIndex('ssi_etd_eta_idx');
            } catch (\Throwable $e) {
            }
        });
    }
};
