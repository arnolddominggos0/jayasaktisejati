<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE shipment_tracks
            ALTER COLUMN check_result
            TYPE jsonb
            USING to_jsonb(check_result)
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE shipment_tracks
            ALTER COLUMN check_result
            TYPE varchar(10)
        ");
    }
};
