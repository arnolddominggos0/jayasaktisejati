<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vessel_plans', function (Blueprint $table) {

            if (!Schema::hasColumn('vessel_plans', 'draft_kpi_total')) {
                $table->integer('draft_kpi_total')->nullable()->after('status');
            }

            if (!Schema::hasColumn('vessel_plans', 'final_kpi_total')) {
                $table->integer('final_kpi_total')->nullable()->after('draft_kpi_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vessel_plans', function (Blueprint $table) {

            if (Schema::hasColumn('vessel_plans', 'draft_kpi_total')) {
                $table->dropColumn('draft_kpi_total');
            }

            if (Schema::hasColumn('vessel_plans', 'final_kpi_total')) {
                $table->dropColumn('final_kpi_total');
            }
        });
    }
};
