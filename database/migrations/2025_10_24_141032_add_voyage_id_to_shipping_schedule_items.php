<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('shipping_schedule_items', 'voyage_id')) {
            Schema::table('shipping_schedule_items', function (Blueprint $table) {
                $table->foreignId('voyage_id')
                    ->nullable()
                    ->constrained('voyages')
                    ->nullOnDelete()
                    ->after('service');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('shipping_schedule_items', 'voyage_id')) {
            Schema::table('shipping_schedule_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('voyage_id');
            });
        }
    }
};
