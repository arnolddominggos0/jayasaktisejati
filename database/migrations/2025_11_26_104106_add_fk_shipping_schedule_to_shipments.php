<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasTable('shipping_schedules') && Schema::hasColumn('shipments', 'shipping_schedule_id')) {
                $table->foreign('shipping_schedule_id')
                    ->references('id')
                    ->on('shipping_schedules')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'shipping_schedule_id')) {
                $table->dropForeign(['shipping_schedule_id']);
            }
        });
    }
};
