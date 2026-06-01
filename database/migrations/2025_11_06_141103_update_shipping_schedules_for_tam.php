<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipping_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('shipping_schedules', 'tam_payload')) {
                $table->jsonb('tam_payload')->nullable();
            }

            if (! Schema::hasColumn('shipping_schedules', 'tam_version')) {
                $table->string('tam_version')->nullable();
            }

            if (! Schema::hasColumn('shipping_schedules', 'tam_draft_path')) {
                $table->string('tam_draft_path')->nullable();
            }

            if (! Schema::hasColumn('shipping_schedules', 'tam_generated_at')) {
                $table->timestamp('tam_generated_at')->nullable();
            }
        });

        DB::statement('CREATE INDEX IF NOT EXISTS shipping_schedules_state_index ON shipping_schedules ("state")');
        DB::statement('CREATE INDEX IF NOT EXISTS shipping_schedules_etd_index ON shipping_schedules ("etd")');
        DB::statement('CREATE INDEX IF NOT EXISTS shipping_schedules_eta_index ON shipping_schedules ("eta")');

        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_constraint
                    WHERE conname = 'shipping_schedules_voyage_id_unique'
                ) THEN
                    ALTER TABLE shipping_schedules
                        ADD CONSTRAINT shipping_schedules_voyage_id_unique UNIQUE (voyage_id);
                END IF;
            END$$;
        ");
    }

    public function down(): void
    {
        Schema::table('shipping_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('shipping_schedules', 'tam_payload')) {
                $table->dropColumn('tam_payload');
            }
            if (Schema::hasColumn('shipping_schedules', 'tam_version')) {
                $table->dropColumn('tam_version');
            }
            if (Schema::hasColumn('shipping_schedules', 'tam_draft_path')) {
                $table->dropColumn('tam_draft_path');
            }
            if (Schema::hasColumn('shipping_schedules', 'tam_generated_at')) {
                $table->dropColumn('tam_generated_at');
            }
        });

        DB::statement('DROP INDEX IF EXISTS shipping_schedules_state_index');
        DB::statement('DROP INDEX IF EXISTS shipping_schedules_etd_index');
        DB::statement('DROP INDEX IF EXISTS shipping_schedules_eta_index');
        DB::statement('ALTER TABLE shipping_schedules DROP CONSTRAINT IF EXISTS shipping_schedules_voyage_id_unique');
    }
};
