<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('briefing_sessions', function (Blueprint $table) {

            // pending activity
            $table->boolean('pending_activity')
                ->default(false);

            $table->text('pending_reason')
                ->nullable();

            // APD request
            $table->string('apd_request_status')
                ->nullable();

            $table->text('apd_request_note')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('briefing_sessions', function (Blueprint $table) {

            $table->dropColumn([
                'pending_activity',
                'pending_reason',
                'apd_request_status',
                'apd_request_note',
            ]);
        });
    }
};
