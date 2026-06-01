<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1.3: Normalize vessel_checks.voyage_id to nullOnDelete + index
        // Canonical: nullable, nullOnDelete, indexed.
        // The May 16 migration already created voyage_id with nullOnDelete + index.
        // This migration verifies the FK is correct, and adds index if missing.
        // Safe to run even if May 16 migration already applied.

        if (! Schema::hasColumn('vessel_checks', 'voyage_id')) {
            // Column doesn't exist — create it
            Schema::table('vessel_checks', function (Blueprint $table) {
                $table->foreignId('voyage_id')
                    ->nullable()
                    ->after('shipping_schedule_id')
                    ->constrained('voyages')
                    ->nullOnDelete();
                $table->index('voyage_id');
            });
            return;
        }

        // Column exists — verify nullOnDelete by dropping and recreating
        // Use Laravel's dropForeign which finds FK by column name automatically
        Schema::table('vessel_checks', function (Blueprint $table) {
            $table->dropForeign(['voyage_id']);
        });

        // Recreate with nullOnDelete
        Schema::table('vessel_checks', function (Blueprint $table) {
            $table->foreign('voyage_id')
                ->references('id')
                ->on('voyages')
                ->nullOnDelete();
        });

        // Add index if missing (idempotent)
        if (! $this->hasIndex('vessel_checks', 'vessel_checks_voyage_id_index')) {
            Schema::table('vessel_checks', function (Blueprint $table) {
                $table->index('voyage_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vessel_checks', 'voyage_id')) {
            Schema::table('vessel_checks', function (Blueprint $table) {
                $table->dropForeign(['voyage_id']);
                $table->foreign('voyage_id')
                    ->references('id')
                    ->on('voyages')
                    ->cascadeOnDelete();
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'pgsql') {
            $result = DB::selectOne(
                "SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?",
                [$table, $indexName]
            );
        } else {
            $result = DB::selectOne(
                "SELECT INDEX_NAME as indexname FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_NAME = ? AND INDEX_NAME = ?",
                [$table, $indexName]
            );
        }

        return $result !== null;
    }
};