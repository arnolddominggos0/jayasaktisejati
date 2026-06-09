<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add appsheet_id bridge column to manpowers.
     *
     * This column stores the AppSheet-side key for each manpower record,
     * enabling the webhook sync to resolve "MP ID" from detail_mp_check
     * into a valid manpowers.id FK without guessing row numbers.
     *
     * Nullable so that records created manually via the Filament admin panel
     * before AppSheet sync is set up remain valid (appsheet_id = null).
     *
     * Unique to enforce one-to-one mapping between AppSheet and Laravel rows.
     */
    public function up(): void
    {
        Schema::table('manpowers', function (Blueprint $table) {
            $table->string('appsheet_id', 64)
                ->nullable()
                ->unique()
                ->after('id')
                ->comment('AppSheet row key (UNIQUEID or custom integer). Used by webhook to resolve MP ID → manpower_id FK.');
        });
    }

    public function down(): void
    {
        Schema::table('manpowers', function (Blueprint $table) {
            $table->dropUnique(['appsheet_id']);
            $table->dropColumn('appsheet_id');
        });
    }
};
