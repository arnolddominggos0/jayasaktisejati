<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('briefing_attendances', function (Blueprint $table) {
            $table->decimal('recheck_temperature', 4, 1)->nullable()->after('recheck_result');
            $table->smallInteger('recheck_bp_systolic')->nullable()->after('recheck_temperature');
            $table->smallInteger('recheck_bp_diastolic')->nullable()->after('recheck_bp_systolic');
            $table->timestamp('recheck_at')->nullable()->after('recheck_bp_diastolic');
        });
    }

    public function down(): void
    {
        Schema::table('briefing_attendances', function (Blueprint $table) {
            $table->dropColumn([
                'recheck_temperature',
                'recheck_bp_systolic',
                'recheck_bp_diastolic',
                'recheck_at',
            ]);
        });
    }
};
