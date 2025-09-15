<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') return;

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION trg_sea_bookings_check_line_consistency()
RETURNS trigger AS $$
DECLARE
    v_line_id bigint;
BEGIN
    IF NEW.voyage_id IS NULL THEN
        RETURN NEW;
    END IF;

    SELECT shipping_line_id INTO v_line_id FROM voyages WHERE id = NEW.voyage_id;
    IF v_line_id IS NULL THEN
        RAISE EXCEPTION 'Voyage % tidak ditemukan', NEW.voyage_id;
    END IF;

    IF NEW.shipping_line_id IS NOT NULL AND NEW.shipping_line_id <> v_line_id THEN
        RAISE EXCEPTION 'Voyage (line_id=%) tidak cocok dengan Shipping Line yang dipilih (line_id=%)', v_line_id, NEW.shipping_line_id;
    END IF;

    IF NEW.shipping_line_id IS NULL THEN
        NEW.shipping_line_id := v_line_id;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS sea_bookings_check_line_consistency ON sea_bookings;

CREATE TRIGGER sea_bookings_check_line_consistency
BEFORE INSERT OR UPDATE OF voyage_id, shipping_line_id ON sea_bookings
FOR EACH ROW EXECUTE FUNCTION trg_sea_bookings_check_line_consistency();
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') return;

        DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS sea_bookings_check_line_consistency ON sea_bookings;
DROP FUNCTION IF EXISTS trg_sea_bookings_check_line_consistency();
SQL);
    }
};
