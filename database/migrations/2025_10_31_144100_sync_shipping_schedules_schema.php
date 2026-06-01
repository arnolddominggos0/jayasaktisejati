<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('shipping_schedules')) {
            Schema::table('shipping_schedules', function (Blueprint $table) {
                if (!Schema::hasColumn('shipping_schedules', 'shipping_line_id')) {
                    $table->foreignId('shipping_line_id')->nullable()->constrained('shipping_lines')->nullOnDelete();
                }
                if (!Schema::hasColumn('shipping_schedules', 'vessel_id')) {
                    $table->foreignId('vessel_id')->nullable()->constrained('vessels')->nullOnDelete();
                }
                if (!Schema::hasColumn('shipping_schedules', 'vessel_name')) {
                    $table->string('vessel_name', 255)->nullable()->after('vessel_id');
                }
                if (!Schema::hasColumn('shipping_schedules', 'period_month')) {
                    $table->date('period_month')->nullable()->index();
                }
                if (!Schema::hasColumn('shipping_schedules', 'voyage_no')) {
                    $table->string('voyage_no', 50)->nullable()->index();
                }
                if (!Schema::hasColumn('shipping_schedules', 'cargo_plan')) {
                    $table->integer('cargo_plan')->nullable();
                }
                if (!Schema::hasColumn('shipping_schedules', 'state')) {
                    $table->string('state', 20)->default('draft')->index();
                }
                if (!Schema::hasColumn('shipping_schedules', 'etd')) {
                    $table->timestampTz('etd')->nullable()->index();
                }
                if (!Schema::hasColumn('shipping_schedules', 'eta')) {
                    $table->timestampTz('eta')->nullable()->index();
                }
            });
        }

        if (Schema::hasTable('shipping_schedule_items')) {
            Schema::table('shipping_schedule_items', function (Blueprint $table) {
                if (Schema::hasColumn('shipping_schedule_items', 'vessel_name')) {
                    $table->dropColumn('vessel_name');
                }
                if (Schema::hasColumn('shipping_schedule_items', 'vessel_capacity')) {
                    $table->dropColumn('vessel_capacity');
                }
                if (Schema::hasColumn('shipping_schedule_items', 'service')) {
                    $table->dropColumn('service');
                }
                if (!Schema::hasColumn('shipping_schedule_items', 'voyage_no')) {
                    $table->string('voyage_no', 50)->nullable()->index();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shipping_schedule_items')) {
            Schema::table('shipping_schedule_items', function (Blueprint $table) {
                if (!Schema::hasColumn('shipping_schedule_items', 'vessel_name')) {
                    $table->string('vessel_name')->nullable();
                }
                if (!Schema::hasColumn('shipping_schedule_items', 'vessel_capacity')) {
                    $table->integer('vessel_capacity')->nullable();
                }
                if (!Schema::hasColumn('shipping_schedule_items', 'service')) {
                    $table->string('service')->nullable();
                }
            });
        }

        if (Schema::hasTable('shipping_schedules')) {
            Schema::table('shipping_schedules', function (Blueprint $table) {
                if (Schema::hasColumn('shipping_schedules', 'vessel_name')) {
                    $table->dropColumn('vessel_name');
                }
                if (Schema::hasColumn('shipping_schedules', 'period_month')) {
                    $table->dropColumn('period_month');
                }
                if (Schema::hasColumn('shipping_schedules', 'voyage_no')) {
                    $table->dropColumn('voyage_no');
                }
                if (Schema::hasColumn('shipping_schedules', 'cargo_plan')) {
                    $table->dropColumn('cargo_plan');
                }
                if (Schema::hasColumn('shipping_schedules', 'vessel_id')) {
                    $table->dropConstrainedForeignId('vessel_id');
                }
                if (Schema::hasColumn('shipping_schedules', 'shipping_line_id')) {
                    $table->dropConstrainedForeignId('shipping_line_id');
                }
            });
        }
    }
};
