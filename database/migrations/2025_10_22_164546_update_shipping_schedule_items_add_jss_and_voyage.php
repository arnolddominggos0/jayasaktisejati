<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_schedule_items', function (Blueprint $table) {
            if (!Schema::hasColumn('shipping_schedule_items', 'voyage_id')) {
                $table->foreignId('voyage_id')
                    ->nullable()
                    ->constrained('voyages')
                    ->nullOnDelete()
                    ->after('jss');
            }
        });

        if (
            Schema::hasColumn('shipping_schedule_items', 'jss') &&
            Schema::hasColumn('shipping_schedule_items', 'service')
        ) {
            DB::statement(<<<'SQL'
                UPDATE shipping_schedule_items
                SET jss = COALESCE(jss, NULLIF(TRIM(service), ''))
                WHERE jss IS NULL AND service IS NOT NULL
            SQL); 
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
