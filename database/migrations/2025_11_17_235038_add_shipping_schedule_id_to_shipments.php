<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (! Schema::hasColumn('shipments', 'shipping_schedule_id')) {
                $table->foreignId('shipping_schedule_id')
                    ->nullable()
                    ->after('voyage_id')
                    ->constrained('shipping_schedules');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'shipping_schedule_id')) {
                $table->dropConstrainedForeignId('shipping_schedule_id');
            }
        });
    }
};
