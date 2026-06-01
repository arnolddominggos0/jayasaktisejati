<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('briefing_attendances', function (Blueprint $table) {
            if (Schema::hasColumn('briefing_attendances', 'bp')) {
                $table->dropColumn('bp');
            }

            if (! Schema::hasColumn('briefing_attendances', 'bp_systolic')) {
                $table->smallInteger('bp_systolic')->nullable()->after('temperature')
                    ->comment('Tekanan darah sistolik (mmHg)');
            }
            if (! Schema::hasColumn('briefing_attendances', 'bp_diastolic')) {
                $table->smallInteger('bp_diastolic')->nullable()->after('bp_systolic')
                    ->comment('Tekanan darah diastolik (mmHg)');
            }
        });

        Schema::table('briefing_attendances', function (Blueprint $table) {
            $table->unique(['session_id', 'manpower_id'], 'uniq_session_mp');
        });
    }

    public function down(): void
    {
        Schema::table('briefing_attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('briefing_attendances', 'bp')) {
                $table->string('bp')->nullable()->after('temperature');
            }

            if (Schema::hasColumn('briefing_attendances', 'bp_systolic')) {
                $table->dropColumn('bp_systolic');
            }
            if (Schema::hasColumn('briefing_attendances', 'bp_diastolic')) {
                $table->dropColumn('bp_diastolic');
            }

            $table->dropUnique('uniq_session_mp');
        });
    }
};
