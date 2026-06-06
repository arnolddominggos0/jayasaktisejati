<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vessel_plan_items', function (Blueprint $table) {
            if (! Schema::hasColumn('vessel_plan_items', 'voyage_no')) {
                $table->string('voyage_no', 50)->nullable()->after('vessel_id');
            }

            if (! Schema::hasColumn('vessel_plan_items', 'planned_etb')) {
                $table->timestamp('planned_etb')->nullable()->after('planned_etd');
            }

            if (! Schema::hasColumn('vessel_plan_items', 'cargo_plan')) {
                $table->unsignedInteger('cargo_plan')->nullable()->after('planned_eta');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vessel_plan_items', function (Blueprint $table) {
            foreach (['voyage_no', 'planned_etb', 'cargo_plan'] as $col) {
                if (Schema::hasColumn('vessel_plan_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
