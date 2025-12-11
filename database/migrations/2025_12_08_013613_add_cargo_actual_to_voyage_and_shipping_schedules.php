<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('voyages')) {
            Schema::table('voyages', function (Blueprint $table) {
                if (!Schema::hasColumn('voyages', 'cargo_actual')) {
                    $table->unsignedInteger('cargo_actual')->nullable()->after('cargo_plan');
                }
                if (!Schema::hasColumn('voyages', 'cargo_actual_reported_at')) {
                    $table->timestamp('cargo_actual_reported_at')->nullable()->after('cargo_actual');
                }
                if (!Schema::hasColumn('voyages', 'cargo_actual_reported_by')) {
                    $table->string('cargo_actual_reported_by')->nullable()->after('cargo_actual_reported_at');
                }
            });
        }

        if (Schema::hasTable('shipping_schedules')) {
            Schema::table('shipping_schedules', function (Blueprint $table) {
                if (!Schema::hasColumn('shipping_schedules', 'cargo_actual')) {
                    $table->unsignedInteger('cargo_actual')->nullable()->after('cargo_plan');
                }
                if (!Schema::hasColumn('shipping_schedules', 'cargo_actual_reported_at')) {
                    $table->timestamp('cargo_actual_reported_at')->nullable()->after('cargo_actual');
                }
                if (!Schema::hasColumn('shipping_schedules', 'cargo_actual_reported_by')) {
                    $table->string('cargo_actual_reported_by')->nullable()->after('cargo_actual_reported_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('voyages')) {
            Schema::table('voyages', function (Blueprint $table) {
                $table->dropColumn(['cargo_actual', 'cargo_actual_reported_at', 'cargo_actual_reported_by']);
            });
        }

        if (Schema::hasTable('shipping_schedules')) {
            Schema::table('shipping_schedules', function (Blueprint $table) {
                $table->dropColumn(['cargo_actual', 'cargo_actual_reported_at', 'cargo_actual_reported_by']);
            });
        }
    }
};
