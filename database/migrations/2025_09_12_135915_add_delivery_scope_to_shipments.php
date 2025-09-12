<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (! Schema::hasColumn('shipments', 'delivery_scope')) {
                $table->string('delivery_scope', 32)->nullable()->after('service_option');
                $table->index('delivery_scope', 'shipments_delivery_scope_index');
            }
        });

        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_constraint WHERE conname = 'shipments_delivery_scope_check'
                ) THEN
                    ALTER TABLE shipments
                    ADD CONSTRAINT shipments_delivery_scope_check
                    CHECK (
                        delivery_scope IS NULL OR delivery_scope IN (
                            'port_to_port','port_to_door','door_to_port','door_to_door'
                        )
                    );
                END IF;
            END$$;
        ");
    }

    public function down(): void
    {
        try { DB::statement('ALTER TABLE shipments DROP CONSTRAINT IF EXISTS shipments_delivery_scope_check;'); } catch (\Throwable $e) {}
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'delivery_scope')) {
                $table->dropIndex('shipments_delivery_scope_index');
                $table->dropColumn('delivery_scope');
            }
        });
    }
};
