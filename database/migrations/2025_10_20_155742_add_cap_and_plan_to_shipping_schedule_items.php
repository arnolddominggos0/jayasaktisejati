<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipping_schedule_items', function (Blueprint $table) {
            if (!Schema::hasColumn('shipping_schedule_items', 'vessel_capacity')) {
                $table->integer('vessel_capacity')->nullable()->after('pod_id');
            }
            if (!Schema::hasColumn('shipping_schedule_items', 'cargo_plan')) {
                $table->integer('cargo_plan')->nullable()->after('vessel_capacity');
            }
            if (!Schema::hasColumn('shipping_schedule_items', 'service')) {
                $table->string('service', 20)->nullable()->after('voyage_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipping_schedule_items', function (Blueprint $table) {
            if (Schema::hasColumn('shipping_schedule_items', 'cargo_plan')) {
                $table->dropColumn('cargo_plan');
            }
            if (Schema::hasColumn('shipping_schedule_items', 'vessel_capacity')) {
                $table->dropColumn('vessel_capacity');
            }
            if (Schema::hasColumn('shipping_schedule_items', 'service')) {
                $table->dropColumn('service');
            }
        });
    }
};
