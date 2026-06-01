<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // shipment_status
        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'shipment_status') THEN
                    CREATE TYPE shipment_status AS ENUM (
                        'draft','pending','pickup','transit','delivered','hold','cancelled'
                    );
                END IF;
            END
            $$;
        ");

        // shipment_mode
        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'shipment_mode') THEN
                    CREATE TYPE shipment_mode AS ENUM ('sea','land');
                END IF;
            END
            $$;
        ");

        // service_type (kategori layanan)
        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'service_type') THEN
                    CREATE TYPE service_type AS ENUM ('sea_freight','land_trucking','car_carrier');
                END IF;
            END
            $$;
        ");

        // cargo_type (jenis muatan)
        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'cargo_type') THEN
                    CREATE TYPE cargo_type AS ENUM ('vehicle','general');
                END IF;
            END
            $$;
        ");
    }

    public function down(): void
    {
        DB::statement("DROP TYPE IF EXISTS cargo_type;");
        DB::statement("DROP TYPE IF EXISTS service_type;");
        DB::statement("DROP TYPE IF EXISTS shipment_mode;");
        DB::statement("DROP TYPE IF EXISTS shipment_status;");
    }
};
