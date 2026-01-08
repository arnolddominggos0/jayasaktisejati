<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'shipping_schedule_id')) {
                $table->foreignId('shipping_schedule_id')
                    ->nullable()
                    ->constrained('shipping_schedules')
                    ->nullOnDelete();
            }
        });

        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1
                    FROM pg_constraint
                    WHERE conname = 'shipments_shipping_schedule_id_foreign'
                ) THEN
                    ALTER TABLE shipments
                    ADD CONSTRAINT shipments_shipping_schedule_id_foreign
                    FOREIGN KEY (shipping_schedule_id)
                    REFERENCES shipping_schedules(id)
                    ON DELETE SET NULL;
                END IF;
            END
            $$;
        ");
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropForeign(['shipping_schedule_id']);
        });
    }
};

