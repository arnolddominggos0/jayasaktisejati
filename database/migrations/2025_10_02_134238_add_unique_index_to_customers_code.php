<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        try {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS customers_code_unique ON customers (code)');
        } catch (\Throwable $e) {
            if (! Schema::hasColumn('customers', 'code')) return;
            DB::statement('ALTER TABLE customers ADD UNIQUE KEY customers_code_unique (code)');
        }
    }

    public function down(): void
    {
        try {
            DB::statement('DROP INDEX IF EXISTS customers_code_unique');
        } catch (\Throwable $e) {
            DB::statement('ALTER TABLE customers DROP INDEX customers_code_unique');
        }
    }
};
