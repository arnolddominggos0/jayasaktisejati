<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (! Schema::hasColumn('shipments', 'cbm_total')) {
                $table->decimal('cbm_total', 10, 3)->nullable();
            }
            if (! Schema::hasColumn('shipments', 'packages_total')) {
                $table->integer('packages_total')->nullable();
            }
            if (! Schema::hasColumn('shipments', 'weight_total')) {
                $table->decimal('weight_total', 12, 2)->nullable();
            }

            if (! Schema::hasColumn('shipments', 'attachments')) {
                $table->json('attachments')->nullable();
            }
            if (! Schema::hasColumn('shipments', 'lcl_items')) {
                $table->json('lcl_items')->nullable();
            }

            if (! Schema::hasColumn('shipments', 'driver_id')) {
                $table->foreignId('driver_id')->nullable();
            }

            if (! Schema::hasColumn('shipments', 'estimated_ready_at')) {
                $table->timestamp('estimated_ready_at')->nullable();
            }

            if (! Schema::hasColumn('shipments', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable();
            }
            if (! Schema::hasColumn('shipments', 'cancelled_by')) {
                $table->foreignId('cancelled_by')->nullable();
            }
            if (! Schema::hasColumn('shipments', 'edited_fields')) {
                $table->json('edited_fields')->nullable();
            }
            if (! Schema::hasColumn('shipments', 'last_edited_by')) {
                $table->foreignId('last_edited_by')->nullable();
            }
        });

        if (Schema::hasColumn('shipments', 'cbm_total')) {
            DB::statement("ALTER TABLE shipments ALTER COLUMN cbm_total TYPE numeric(10,3) USING cbm_total::numeric");
        }
        if (Schema::hasColumn('shipments', 'weight_total')) {
            DB::statement("ALTER TABLE shipments ALTER COLUMN weight_total TYPE numeric(12,2) USING weight_total::numeric");
        }

        if (Schema::hasTable('drivers')) {
            DB::statement("
                DO $$
                BEGIN
                    IF NOT EXISTS (
                        SELECT 1 FROM pg_constraint WHERE conname = 'shipments_driver_id_foreign'
                    ) THEN
                        ALTER TABLE shipments
                            ADD CONSTRAINT shipments_driver_id_foreign
                            FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL;
                    END IF;
                END$$;
            ");
        }
        if (Schema::hasTable('users')) {
            DB::statement("
                DO $$
                BEGIN
                    IF NOT EXISTS (
                        SELECT 1 FROM pg_constraint WHERE conname = 'shipments_cancelled_by_foreign'
                    ) THEN
                        ALTER TABLE shipments
                            ADD CONSTRAINT shipments_cancelled_by_foreign
                            FOREIGN KEY (cancelled_by) REFERENCES users(id) ON DELETE SET NULL;
                    END IF;
                END$$;
            ");
            DB::statement("
                DO $$
                BEGIN
                    IF NOT EXISTS (
                        SELECT 1 FROM pg_constraint WHERE conname = 'shipments_last_edited_by_foreign'
                    ) THEN
                        ALTER TABLE shipments
                            ADD CONSTRAINT shipments_last_edited_by_foreign
                            FOREIGN KEY (last_edited_by) REFERENCES users(id) ON DELETE SET NULL;
                    END IF;
                END$$;
            ");
        }
    }

    public function down(): void
    {
        foreach ([
            'shipments_driver_id_foreign',
            'shipments_cancelled_by_foreign',
            'shipments_last_edited_by_foreign',
        ] as $fk) {
            try { DB::statement("ALTER TABLE shipments DROP CONSTRAINT IF EXISTS {$fk};"); } catch (\Throwable $e) {}
        }

        Schema::table('shipments', function (Blueprint $table) {
            foreach ([
                'cbm_total','packages_total','weight_total',
                'attachments','lcl_items',
                'driver_id','estimated_ready_at',
                'cancelled_at','cancelled_by',
                'edited_fields','last_edited_by',
            ] as $col) {
                if (Schema::hasColumn('shipments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
