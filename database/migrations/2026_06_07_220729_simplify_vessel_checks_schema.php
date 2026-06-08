<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate existing status values first
        DB::statement("
            UPDATE vessel_checks
            SET status = CASE
                WHEN status = 'on_schedule'     THEN 'ok'
                WHEN status = 'potential_delay' THEN 'late'
                ELSE status
            END
        ");

        Schema::table('vessel_checks', function (Blueprint $table) {
            foreach (['etd_plan', 'etd_current', 'source'] as $col) {
                if (Schema::hasColumn('vessel_checks', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        DB::statement("ALTER TABLE vessel_checks ALTER COLUMN status SET DEFAULT 'ok'");
    }

    public function down(): void
    {
        Schema::table('vessel_checks', function (Blueprint $table) {
            $table->dateTime('etd_plan')->nullable();
            $table->dateTime('etd_current')->nullable();
            $table->string('source')->nullable();
        });

        DB::statement("ALTER TABLE vessel_checks ALTER COLUMN status SET DEFAULT 'on_schedule'");

        DB::statement("
            UPDATE vessel_checks
            SET status = CASE
                WHEN status = 'ok'   THEN 'on_schedule'
                WHEN status = 'late' THEN 'potential_delay'
                ELSE status
            END
        ");
    }
};
