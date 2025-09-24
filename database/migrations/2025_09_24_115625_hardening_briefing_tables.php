<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        if (Schema::hasTable('briefing_attendances')) {
            $this->createIndexIfNotExists(
                'briefing_attendance_unique',
                'briefing_attendances',
                'CREATE UNIQUE INDEX IF NOT EXISTS briefing_attendance_unique ON briefing_attendances (session_id, manpower_id)'
            );

            $this->createIndexIfNotExists(
                'briefing_attendances_session_idx',
                'briefing_attendances',
                'CREATE INDEX IF NOT EXISTS briefing_attendances_session_idx ON briefing_attendances (session_id)'
            );

            $this->createIndexIfNotExists(
                'briefing_attendances_manpower_idx',
                'briefing_attendances',
                'CREATE INDEX IF NOT EXISTS briefing_attendances_manpower_idx ON briefing_attendances (manpower_id)'
            );

            $this->createIndexIfNotExists(
                'briefing_attendances_status_idx',
                'briefing_attendances',
                'CREATE INDEX IF NOT EXISTS briefing_attendances_status_idx ON briefing_attendances (attendance_status)'
            );
        }

        if (Schema::hasTable('briefing_checklists')) {
            $this->createIndexIfNotExists(
                'briefing_checklists_unique_item',
                'briefing_checklists',
                'CREATE UNIQUE INDEX IF NOT EXISTS briefing_checklists_unique_item ON briefing_checklists (session_id, item)'
            );

            $this->createIndexIfNotExists(
                'briefing_checklists_session_idx',
                'briefing_checklists',
                'CREATE INDEX IF NOT EXISTS briefing_checklists_session_idx ON briefing_checklists (session_id)'
            );

            $this->createIndexIfNotExists(
                'briefing_checklists_status_idx',
                'briefing_checklists',
                'CREATE INDEX IF NOT EXISTS briefing_checklists_status_idx ON briefing_checklists (status)'
            );
        }

        if (Schema::hasTable('briefing_sessions')) {
            $this->createIndexIfNotExists(
                'briefing_sessions_date_idx',
                'briefing_sessions',
                'CREATE INDEX IF NOT EXISTS briefing_sessions_date_idx ON briefing_sessions (date)'
            );

            $this->createIndexIfNotExists(
                'briefing_sessions_depot_idx',
                'briefing_sessions',
                'CREATE INDEX IF NOT EXISTS briefing_sessions_depot_idx ON briefing_sessions (depot_id)'
            );

            $this->createIndexIfNotExists(
                'briefing_sessions_coord_idx',
                'briefing_sessions',
                'CREATE INDEX IF NOT EXISTS briefing_sessions_coord_idx ON briefing_sessions (coordinator_user_id)'
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') return;

        DB::statement('DROP INDEX IF EXISTS briefing_attendance_unique');
        DB::statement('DROP INDEX IF EXISTS briefing_attendances_session_idx');
        DB::statement('DROP INDEX IF EXISTS briefing_attendances_manpower_idx');
        DB::statement('DROP INDEX IF EXISTS briefing_attendances_status_idx');

        DB::statement('DROP INDEX IF EXISTS briefing_checklists_unique_item');
        DB::statement('DROP INDEX IF EXISTS briefing_checklists_session_idx');
        DB::statement('DROP INDEX IF EXISTS briefing_checklists_status_idx');

        DB::statement('DROP INDEX IF EXISTS briefing_sessions_date_idx');
        DB::statement('DROP INDEX IF EXISTS briefing_sessions_depot_idx');
        DB::statement('DROP INDEX IF EXISTS briefing_sessions_coord_idx');
    }


    private function createIndexIfNotExists(string $indexName, string $tableName, string $createSql): void
    {
        $exists = DB::scalar("
            SELECT 1
            FROM pg_indexes
            WHERE schemaname = ANY(current_schemas(false))
              AND tablename = ?
              AND indexname = ?
            LIMIT 1
        ", [$tableName, $indexName]);

        if (!$exists) {
            DB::statement($createSql);
        }
    }
};
