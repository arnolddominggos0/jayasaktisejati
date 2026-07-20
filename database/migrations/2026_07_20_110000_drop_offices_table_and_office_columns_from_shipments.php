<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Office Retirement — Phase 3 (Database Retirement), 2026-07-20.
 *
 * Final step of the Office → Branch migration. All code dependencies on
 * Office were removed in Phase 1 & 2 (see docs/master-office/
 * OFFICE-RETIREMENT-PHASE-1-2-REPORT.md). This migration removes the last
 * schema traces:
 *   - the two nullable FK columns shipments.origin_office_id /
 *     destination_office_id (never written; 0 non-null rows at retirement),
 *   - the offices table itself (0 rows at retirement).
 *
 * Old migrations are intentionally left untouched; on `migrate:fresh` they
 * still create the offices table + FK columns, and this migration (running
 * last, by timestamp) drops them — the standard additive Laravel pattern.
 *
 * Reversible: down() faithfully recreates the offices table (mirroring
 * 2025_08_15_075624_create_offices_table) and re-adds both nullable FK
 * columns (mirroring 2025_09_14_150000_create_shipments_table).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop FK constraints + columns from shipments first (they reference
        // offices, so they must go before the table can be dropped).
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'origin_office_id')) {
                $table->dropConstrainedForeignId('origin_office_id');
            }
            if (Schema::hasColumn('shipments', 'destination_office_id')) {
                $table->dropConstrainedForeignId('destination_office_id');
            }
        });

        Schema::dropIfExists('offices');
    }

    public function down(): void
    {
        // Recreate offices first (mirrors 2025_08_15_075624_create_offices_table)...
        if (! Schema::hasTable('offices')) {
            Schema::create('offices', function (Blueprint $table) {
                $table->id();
                $table->string('code', 32)->unique();
                $table->string('name');
                $table->string('city')->nullable();
                $table->string('address')->nullable();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
            });
        }

        // ...then re-add the nullable FK columns (mirrors create_shipments_table).
        Schema::table('shipments', function (Blueprint $table) {
            if (! Schema::hasColumn('shipments', 'origin_office_id')) {
                $table->foreignId('origin_office_id')->nullable()->constrained('offices')->nullOnDelete();
            }
            if (! Schema::hasColumn('shipments', 'destination_office_id')) {
                $table->foreignId('destination_office_id')->nullable()->constrained('offices')->nullOnDelete();
            }
        });
    }
};
