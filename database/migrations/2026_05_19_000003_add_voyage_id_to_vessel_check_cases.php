<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1.4: Add voyage_id to vessel_check_cases
        // VesselCheckCase must have a direct link to Voyage (not just via ShippingSchedule).
        // This enables:
        // - Direct voyage-to-case queries in monitoring
        // - Phase 10 integration (VesselCheckCase in monitoring matrix)
        // - Decoupled relationship from ShippingSchedule

        if (! Schema::hasColumn('vessel_check_cases', 'voyage_id')) {
            Schema::table('vessel_check_cases', function (Blueprint $table) {
                $table->foreignId('voyage_id')
                    ->nullable()
                    ->after('shipping_schedule_id')
                    ->constrained('voyages')
                    ->nullOnDelete();

                $table->index('voyage_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vessel_check_cases', 'voyage_id')) {
            Schema::table('vessel_check_cases', function (Blueprint $table) {
                $table->dropForeign(['voyage_id']);
                $table->dropIndex(['voyage_id']);
                $table->dropColumn('voyage_id');
            });
        }
    }
};