<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the legacy date+depot unique constraint from briefing_sessions.
 * The actual constraint name is briefing_sessions_date_depot_unique.
 * SC.3B.19 replaces it with unique(shipment_id) (done in previous migration).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Use raw DROP to handle any naming variation
        DB::statement("
            ALTER TABLE briefing_sessions
            DROP CONSTRAINT IF EXISTS briefing_sessions_date_depot_unique
        ");

        // Also try the _id variant in case it differs by install
        DB::statement("
            ALTER TABLE briefing_sessions
            DROP CONSTRAINT IF EXISTS briefing_sessions_date_depot_id_unique
        ");
    }

    public function down(): void
    {
        // Restore: add unique back (only valid if no duplicates exist)
        Schema::table('briefing_sessions', function (Blueprint $table) {
            $table->unique(['date', 'depot_id']);
        });
    }
};
