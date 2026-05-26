<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('briefing_attendances', function (Blueprint $table) {

            if (!Schema::hasColumn('briefing_attendances', 'health_complaint')) {
                $table->text('health_complaint')->nullable();
            }

            if (!Schema::hasColumn('briefing_attendances', 'recheck_result')) {
                $table->string('recheck_result')->nullable();
            }

            if (!Schema::hasColumn('briefing_attendances', 'rest_started_at')) {
                $table->timestamp('rest_started_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('briefing_attendances', function (Blueprint $table) {

            $table->dropColumn([
                'health_complaint',
                'recheck_result',
                'rest_started_at',
            ]);
        });
    }
};
