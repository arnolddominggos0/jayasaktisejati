<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('briefing_attendances', function (Blueprint $table) {
            $table->timestamp('rest_started_at')
                ->nullable()
                ->after('health_complaint');

            $table->string('recheck_result')
                ->nullable()
                ->after('rest_started_at'); // fit | unfit
        });
    }

    public function down(): void
    {
        Schema::table('briefing_attendances', function (Blueprint $table) {
            $table->dropColumn([
                'rest_started_at',
                'recheck_result',
            ]);
        });
    }
};
