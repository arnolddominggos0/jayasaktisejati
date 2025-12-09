<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shipment_tracks', function (Blueprint $table) {
            $table->dateTime('plan_loading_time_at')->nullable()->after('tracked_at');
            $table->dateTime('plan_closing_time_at')->nullable()->after('plan_loading_time_at');
            $table->json('checkseet')->nullable()->after('plan_closing_time_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_tracks', function (Blueprint $table) {
            $table->dropColumn(['plan_loading_time_at', 'plan_closing_time_at', 'checkseet']);
        });
    }
};
