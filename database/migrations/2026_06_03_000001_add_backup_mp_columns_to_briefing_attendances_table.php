<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('briefing_attendances', function (Blueprint $table) {

            if (! Schema::hasColumn('briefing_attendances', 'mp_type')) {
                $table->string('mp_type', 16)->default('regular')->after('manpower_id');
            }

            if (! Schema::hasColumn('briefing_attendances', 'backup_name')) {
                $table->string('backup_name')->nullable()->after('mp_type');
            }

            $table->foreignId('manpower_id')->nullable()->change();
        });

        Schema::table('briefing_attendances', function (Blueprint $table) {
            $table->dropUnique('briefing_attendance_unique');
            $table->unique(['session_id', 'manpower_id', 'mp_type', 'backup_name'], 'briefing_attendance_unique');
        });
    }

    public function down(): void
    {
        Schema::table('briefing_attendances', function (Blueprint $table) {
            $table->dropUnique('briefing_attendance_unique');
            $table->unique(['session_id', 'manpower_id'], 'briefing_attendance_unique');
        });

        Schema::table('briefing_attendances', function (Blueprint $table) {
            $table->dropColumn(['mp_type', 'backup_name']);
            $table->foreignId('manpower_id')->nullable(false)->change();
        });
    }
};
