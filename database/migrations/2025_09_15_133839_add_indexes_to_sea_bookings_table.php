<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sea_bookings', function (Blueprint $table) {
            $table->index(['shipping_line_id', 'voyage_id'], 'sea_bookings_line_voyage_idx');

            $table->index('voyage_id', 'sea_bookings_voyage_idx');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE sea_bookings DROP CONSTRAINT IF EXISTS sea_bookings_shipping_line_id_foreign');
            DB::statement('ALTER TABLE sea_bookings
                ADD CONSTRAINT sea_bookings_shipping_line_id_foreign
                FOREIGN KEY (shipping_line_id) REFERENCES shipping_lines(id)
                ON UPDATE CASCADE ON DELETE RESTRICT
            ');
        }
    }

    public function down(): void
    {
        Schema::table('sea_bookings', function (Blueprint $table) {
            $table->dropIndex('sea_bookings_line_voyage_idx');
            $table->dropIndex('sea_bookings_voyage_idx');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE sea_bookings DROP CONSTRAINT IF EXISTS sea_bookings_shipping_line_id_foreign');
            DB::statement('ALTER TABLE sea_bookings
                ADD CONSTRAINT sea_bookings_shipping_line_id_foreign
                FOREIGN KEY (shipping_line_id) REFERENCES shipping_lines(id)
                ON UPDATE CASCADE ON DELETE CASCADE
            ');
        }
    }
};
