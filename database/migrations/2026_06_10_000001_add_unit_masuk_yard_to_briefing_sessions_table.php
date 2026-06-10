<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('briefing_sessions', function (Blueprint $table) {
            $table->integer('unit_masuk_yard')
                ->nullable()
                ->after('summary_headcount');
        });
    }

    public function down(): void
    {
        Schema::table('briefing_sessions', function (Blueprint $table) {
            $table->dropColumn('unit_masuk_yard');
        });
    }
};
