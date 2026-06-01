<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1.1: vessel_plan_items.voyage_id: cascadeOnDelete → nullOnDelete
        // Voyage must survive plan revisions. Deleting Voyage should NOT delete plan items.
        if (Schema::hasColumn('vessel_plan_items', 'voyage_id')) {
            $this->alterForeignKey('vessel_plan_items', 'voyage_id', 'nullOnDelete');
        }

        // 1.2: shipping_schedules.voyage_id: cascadeOnDelete → nullOnDelete
        // ShippingSchedule is transitional. Deleting Voyage should NOT destroy schedule records.
        if (Schema::hasColumn('shipping_schedules', 'voyage_id')) {
            $this->alterForeignKey('shipping_schedules', 'voyage_id', 'nullOnDelete');
        }
    }

    public function down(): void
    {
        // Revert 1.2: shipping_schedules → cascadeOnDelete
        if (Schema::hasColumn('shipping_schedules', 'voyage_id')) {
            $this->alterForeignKey('shipping_schedules', 'voyage_id', 'cascadeOnDelete');
        }

        // Revert 1.1: vessel_plan_items → cascadeOnDelete
        if (Schema::hasColumn('vessel_plan_items', 'voyage_id')) {
            $this->alterForeignKey('vessel_plan_items', 'voyage_id', 'cascadeOnDelete');
        }
    }

    private function alterForeignKey(string $table, string $column, string $onDelete): void
    {
        // Use Laravel's dropForeign with array syntax — automatically resolves FK name
        Schema::table($table, function (Blueprint $tableObj) use ($column, $onDelete) {
            $tableObj->dropForeign([$column]);
        });

        // Recreate with new onDelete
        Schema::table($table, function (Blueprint $tableObj) use ($column, $onDelete) {
            if ($onDelete === 'nullOnDelete') {
                $tableObj->foreign($column)->references('id')->on('voyages')->nullOnDelete();
            } else {
                $tableObj->foreign($column)->references('id')->on('voyages')->cascadeOnDelete();
            }
        });
    }
};