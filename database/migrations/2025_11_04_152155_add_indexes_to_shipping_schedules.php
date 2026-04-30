<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipping_schedules', function (Blueprint $table) {
            $table->index(['state', 'etd'], 'idx_shipping_schedules_state_etd');
            $table->index(['state', 'eta'], 'idx_shipping_schedules_state_eta');
            $table->index(['voyage_id'], 'idx_shipping_schedules_voyage');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_schedules', function (Blueprint $table) {
            $table->dropIndex('idx_shipping_schedules_state_etd');
            $table->dropIndex('idx_shipping_schedules_state_eta');
            $table->dropIndex('idx_shipping_schedules_voyage');
        });
    }
};
