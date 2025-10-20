<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('shipments', 'voyage_id')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->unsignedBigInteger('voyage_id')->nullable();
            });
        }

        if (Schema::hasColumn('shipments', 'voyage_id') && Schema::hasTable('voyages')) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("
                    DO $$
                    BEGIN
                        IF NOT EXISTS (
                            SELECT 1
                            FROM pg_constraint
                            WHERE conname = 'shipments_voyage_id_foreign'
                        ) THEN
                            ALTER TABLE shipments
                                ADD CONSTRAINT shipments_voyage_id_foreign
                                FOREIGN KEY (voyage_id) REFERENCES voyages(id)
                                ON DELETE SET NULL;
                        END IF;
                    END$$;
                ");
            } else {
                Schema::table('shipments', function (Blueprint $table) {
                    $table->foreign('voyage_id')->references('id')->on('voyages')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('shipments', 'voyage_id')) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE shipments DROP CONSTRAINT IF EXISTS shipments_voyage_id_foreign');
            } else {
                Schema::table('shipments', function (Blueprint $table) {
                    try {
                        $table->dropForeign(['voyage_id']);
                    } catch (\Throwable $e) {
                    }
                });
            }
            Schema::table('shipments', function (Blueprint $table) {
                try {
                    $table->dropColumn('voyage_id');
                } catch (\Throwable $e) {
                }
            });
        }
    }
};
