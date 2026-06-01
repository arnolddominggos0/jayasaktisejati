<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('armada_maintenances', function (Blueprint $table) {
            if (! Schema::hasColumn('armada_maintenances', 'reason_code')) $table->string('reason_code', 50)->nullable()->after('armada_id');
            if (! Schema::hasColumn('armada_maintenances', 'reason'))      $table->text('reason')->nullable()->after('reason_code');
            if (! Schema::hasColumn('armada_maintenances', 'started_at'))  $table->timestamp('started_at')->nullable()->after('reason');
            if (! Schema::hasColumn('armada_maintenances', 'closed_at'))   $table->timestamp('closed_at')->nullable()->after('started_at');
            if (! Schema::hasColumn('armada_maintenances', 'note'))        $table->text('note')->nullable()->after('odometer');
        });

        if (Schema::hasColumn('armada_maintenances', 'title')) {
            DB::statement("UPDATE armada_maintenances SET reason = COALESCE(reason, title) WHERE title IS NOT NULL AND (reason IS NULL OR reason = '')");
        }
        if (Schema::hasColumn('armada_maintenances', 'planned_at')) {
            DB::statement("UPDATE armada_maintenances SET started_at = COALESCE(started_at, planned_at::timestamp) WHERE planned_at IS NOT NULL AND started_at IS NULL");
        }
        if (Schema::hasColumn('armada_maintenances', 'done_at')) {
            DB::statement("UPDATE armada_maintenances SET closed_at = COALESCE(closed_at, done_at::timestamp) WHERE done_at IS NOT NULL AND closed_at IS NULL");
        }
        if (Schema::hasColumn('armada_maintenances', 'notes')) {
            DB::statement("UPDATE armada_maintenances SET note = COALESCE(note, notes) WHERE notes IS NOT NULL AND (note IS NULL OR note = '')");
        }
        DB::statement("
            UPDATE armada_maintenances
            SET reason_code = CASE
                WHEN reason ILIKE '%oli%'                                   THEN 'oil'
                WHEN reason ILIKE '%ban%' OR reason ILIKE '%velg%'          THEN 'tire'
                WHEN reason ILIKE '%rem%'                                   THEN 'brake'
                WHEN reason ILIKE '%listrik%' OR reason ILIKE '%kelistrikan%' THEN 'electrical'
                WHEN reason ILIKE '%cat%' OR reason ILIKE '%body%'          THEN 'body'
                WHEN reason ILIKE '%inspeksi%'                              THEN 'inspection'
                WHEN reason ILIKE '%jadwal%' OR reason ILIKE '%service%'    THEN 'scheduled'
                WHEN reason ILIKE '%mogok%' OR reason ILIKE '%rusak%' OR reason ILIKE '%kerusakan%' THEN 'breakdown'
                ELSE reason_code
            END
            WHERE reason_code IS NULL
        ");

        DB::statement("ALTER TABLE armada_maintenances ALTER COLUMN started_at SET DEFAULT now()");
        DB::statement("UPDATE armada_maintenances SET started_at = COALESCE(started_at, now()) WHERE started_at IS NULL");
        DB::statement("ALTER TABLE armada_maintenances ALTER COLUMN started_at SET NOT NULL");

        DB::statement("ALTER TABLE armada_maintenances DROP CONSTRAINT IF EXISTS armada_maint_ck");
        DB::statement("
            ALTER TABLE armada_maintenances
            ADD CONSTRAINT armada_maint_ck
            CHECK (closed_at IS NULL OR closed_at >= started_at)
        ");

        DB::statement("ALTER TABLE armada_maintenances DROP CONSTRAINT IF EXISTS armada_maint_reason_ck");
        DB::statement("
            ALTER TABLE armada_maintenances
            ADD CONSTRAINT armada_maint_reason_ck
            CHECK (
                reason_code IS NULL OR reason_code IN
                ('scheduled','oil','tire','brake','electrical','body','inspection','breakdown','other')
            )
        ");

        DB::statement("
            CREATE UNIQUE INDEX IF NOT EXISTS armada_maint_open_unique
            ON armada_maintenances (armada_id)
            WHERE closed_at IS NULL
        ");

        DB::statement("CREATE INDEX IF NOT EXISTS armada_maint_armada_started_idx ON armada_maintenances (armada_id, started_at DESC)");
        DB::statement("CREATE INDEX IF NOT EXISTS armada_maint_reason_idx ON armada_maintenances (reason_code)");

        Schema::table('armada_maintenances', function (Blueprint $table) {
            if (Schema::hasColumn('armada_maintenances', 'title'))      $table->dropColumn('title');
            if (Schema::hasColumn('armada_maintenances', 'planned_at')) $table->dropColumn('planned_at');
            if (Schema::hasColumn('armada_maintenances', 'done_at'))    $table->dropColumn('done_at');
            if (Schema::hasColumn('armada_maintenances', 'notes'))      $table->dropColumn('notes');
            if (Schema::hasColumn('armada_maintenances', 'cost'))       $table->dropColumn('cost');
        });
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS armada_maint_open_unique");
        DB::statement("DROP INDEX IF EXISTS armada_maint_reason_idx");
        DB::statement("ALTER TABLE armada_maintenances DROP CONSTRAINT IF EXISTS armada_maint_ck");
        DB::statement("ALTER TABLE armada_maintenances DROP CONSTRAINT IF EXISTS armada_maint_reason_ck");
        DB::statement("ALTER TABLE armada_maintenances ALTER COLUMN started_at DROP NOT NULL");
        DB::statement("ALTER TABLE armada_maintenances ALTER COLUMN started_at DROP DEFAULT");

        Schema::table('armada_maintenances', function (Blueprint $table) {
            if (Schema::hasColumn('armada_maintenances', 'reason_code')) $table->dropColumn('reason_code');
            if (Schema::hasColumn('armada_maintenances', 'reason'))      $table->dropColumn('reason');
            if (Schema::hasColumn('armada_maintenances', 'started_at'))  $table->dropColumn('started_at');
            if (Schema::hasColumn('armada_maintenances', 'closed_at'))   $table->dropColumn('closed_at');
            if (Schema::hasColumn('armada_maintenances', 'note'))        $table->dropColumn('note');
        });
    }
};
