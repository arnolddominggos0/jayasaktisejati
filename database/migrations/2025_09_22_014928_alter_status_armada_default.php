<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE armadas ALTER COLUMN status SET DEFAULT 'Available'");
        DB::statement("UPDATE armadas SET status = 'Available' WHERE status IS NULL OR status = ''");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE armadas ALTER COLUMN status DROP DEFAULT");
    }
};
