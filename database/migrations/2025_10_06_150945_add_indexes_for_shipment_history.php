<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (! $this->hasIndex($table, 'shipments_status_idx')) {
                $table->index('status', 'shipments_status_idx');
            }
            if (! $this->hasIndex($table, 'shipments_cancelled_at_idx')) {
                $table->index('cancelled_at', 'shipments_cancelled_at_idx');
            }
            if (! $this->hasIndex($table, 'shipments_updated_at_idx')) {
                $table->index('updated_at', 'shipments_updated_at_idx');
            }
            if (! $this->hasIndex($table, 'shipments_branch_id_status_idx')) {
                $table->index(['branch_id', 'status'], 'shipments_branch_id_status_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex('shipments_branch_id_status_idx');
            $table->dropIndex('shipments_updated_at_idx');
            $table->dropIndex('shipments_cancelled_at_idx');
            $table->dropIndex('shipments_status_idx');
        });
    }

    private function hasIndex(Blueprint $table, string $index): bool
    {
        return false;
    }
};
