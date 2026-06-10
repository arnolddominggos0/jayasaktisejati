<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the legacy (session_id, manpower_id) unique index that pre-dates backup MP support.
     *
     * The correct composite unique index `briefing_attendance_unique` on
     * (session_id, manpower_id, mp_type, backup_name) already exists and handles all cases.
     *
     * Leaving `uniq_session_mp` in place blocks inserting two backup MPs in the same session
     * because both rows have manpower_id = NULL, which violates (session_id, NULL) uniqueness
     * in PostgreSQL.
     */
    public function up(): void
    {
        // In PostgreSQL the legacy unique index was created as a table constraint
        // (via Schema::table->unique()), so it must be dropped as a constraint,
        // not with DROP INDEX directly.
        DB::statement('ALTER TABLE briefing_attendances DROP CONSTRAINT IF EXISTS uniq_session_mp');
    }

    public function down(): void
    {
        // Restore only if manpower_id is NOT NULL (legacy constraint cannot handle NULLs).
        // This will fail if backup MP rows (manpower_id = NULL) exist — expected.
        Schema::table('briefing_attendances', function ($table) {
            $table->unique(['session_id', 'manpower_id'], 'uniq_session_mp');
        });
    }
};
