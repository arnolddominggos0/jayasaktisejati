<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("
            UPDATE shipments
            SET doc_number = 'MISSING-' || to_char(NOW(), 'YYYYMMDDHH24MISS')
            WHERE request_type = 'sppb_do' AND doc_number IS NULL
        ");

        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'shipments_code_unique') THEN
                    ALTER TABLE shipments
                        ADD CONSTRAINT shipments_code_unique UNIQUE (code);
                END IF;
            END$$;
        ");

        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'shipments_doc_required_on_sppb_do') THEN
                    ALTER TABLE shipments
                        ADD CONSTRAINT shipments_doc_required_on_sppb_do
                        CHECK (request_type <> 'sppb_do' OR doc_number IS NOT NULL);
                END IF;
            END$$;
        ");


        foreach ([
            ['shipments_status_index',               'CREATE INDEX shipments_status_index               ON shipments (status)'],
            ['shipments_mode_index',                 'CREATE INDEX shipments_mode_index                 ON shipments (mode)'],
            ['shipments_service_type_option_index',  'CREATE INDEX shipments_service_type_option_index  ON shipments (service_type, service_option)'],
            ['shipments_customer_receiver_index',    'CREATE INDEX shipments_customer_receiver_index    ON shipments (customer_id, receiver_id)'],
            ['shipments_offices_index',              'CREATE INDEX shipments_offices_index              ON shipments (origin_office_id, destination_office_id)'],
            ['shipments_schedule_index',             'CREATE INDEX shipments_schedule_index             ON shipments (schedule_id)'],
            ['shipments_eta_index',                  'CREATE INDEX shipments_eta_index                  ON shipments (eta)'],
            ['shipments_updated_at_index',           'CREATE INDEX shipments_updated_at_index           ON shipments (updated_at)'],
        ] as [$name, $sql]) {
            DB::statement("
                DO $$
                BEGIN
                    IF NOT EXISTS (
                        SELECT 1
                        FROM pg_class c
                        JOIN pg_namespace n ON n.oid = c.relnamespace
                        WHERE c.relkind = 'i' AND c.relname = '{$name}'
                    ) THEN
                        {$sql};
                    END IF;
                END$$;
            ");
        }
    }

    public function down(): void
    {
        foreach ([
            "ALTER TABLE shipments DROP CONSTRAINT IF EXISTS shipments_doc_required_on_sppb_do",
            "ALTER TABLE shipments DROP CONSTRAINT IF EXISTS shipments_code_unique",
            "DROP INDEX IF EXISTS shipments_status_index",
            "DROP INDEX IF EXISTS shipments_mode_index",
            "DROP INDEX IF EXISTS shipments_service_type_option_index",
            "DROP INDEX IF EXISTS shipments_customer_receiver_index",
            "DROP INDEX IF EXISTS shipments_offices_index",
            "DROP INDEX IF EXISTS shipments_schedule_index",
            "DROP INDEX IF EXISTS shipments_eta_index",
            "DROP INDEX IF EXISTS shipments_updated_at_index",
        ] as $sql) {
            try { DB::statement($sql); } catch (\Throwable $e) {}
        }
    }
};
