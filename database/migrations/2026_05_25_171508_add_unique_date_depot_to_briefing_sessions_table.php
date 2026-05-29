<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('briefing_sessions', function (Blueprint $table) {
            $table->unique(
                ['date', 'depot_id'],
                'briefing_sessions_date_depot_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('briefing_sessions', function (Blueprint $table) {
            $table->dropUnique('briefing_sessions_date_depot_unique');
        });
    }
};
