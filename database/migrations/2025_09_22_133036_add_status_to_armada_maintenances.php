<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('armada_maintenances')) return;

        Schema::table('armada_maintenances', function (Blueprint $table) {
            if (! Schema::hasColumn('armada_maintenances', 'status')) {
                $table->string('status')->default('scheduled')->after('closed_at');
            }
        });

        DB::statement("
            UPDATE armada_maintenances
            SET status = CASE
                WHEN started_at IS NULL AND closed_at IS NULL THEN 'scheduled'
                WHEN started_at IS NOT NULL AND closed_at IS NULL THEN 'in_progress'
                WHEN started_at IS NOT NULL AND closed_at IS NOT NULL THEN 'closed'
                ELSE 'scheduled'
            END
            WHERE status IS NULL OR status NOT IN ('scheduled','in_progress','closed')
        ");

        DB::statement("ALTER TABLE armada_maintenances DROP CONSTRAINT IF EXISTS armada_maint_ck");
        DB::statement("
            ALTER TABLE armada_maintenances
            ADD CONSTRAINT armada_maint_ck CHECK (
                (
                    status = 'scheduled'
                    AND started_at IS NULL
                    AND closed_at  IS NULL
                ) OR (
                    status = 'in_progress'
                    AND started_at IS NOT NULL
                    AND closed_at  IS NULL
                ) OR (
                    status = 'closed'
                    AND started_at IS NOT NULL
                    AND closed_at  IS NOT NULL
                    AND closed_at >= started_at
                )
            )
        ");

        DB::statement("ALTER TABLE armada_maintenances DROP CONSTRAINT IF EXISTS armada_maint_status_ck");
        DB::statement("
            ALTER TABLE armada_maintenances
            ADD CONSTRAINT armada_maint_status_ck CHECK (
                status IN ('scheduled','in_progress','closed')
            )
        ");
    }

    public function down(): void
    {
        if (! Schema::hasTable('armada_maintenances')) return;

        DB::statement("ALTER TABLE armada_maintenances DROP CONSTRAINT IF EXISTS armada_maint_status_ck");
        DB::statement("ALTER TABLE armada_maintenances DROP CONSTRAINT IF EXISTS armada_maint_ck");

        Schema::table('armada_maintenances', function (Blueprint $table) {
            if (Schema::hasColumn('armada_maintenances', 'status')) {
                $table->dropColumn('status');
            }
        });

        DB::statement("
            ALTER TABLE armada_maintenances
            ADD CONSTRAINT armada_maint_ck CHECK ((closed_at IS NULL) OR (closed_at >= started_at))
        ");
    }
};
