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
            $table->dateTime('actual_loading_time_at')->nullable()->after('checkseet');
            $table->dateTime('actual_closing_time_at')->nullable()->after('actual_loading_time_at');
            $table->dateTime('actual_berthing_time_at')->nullable()->after('actual_closing_time_at');
            $table->dateTime('actual_unloading_start_time_at')->nullable()->after('actual_berthing_time_at');
            $table->dateTime('actual_unloading_end_time_at')->nullable()->after('actual_unloading_start_time_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_tracks', function (Blueprint $table) {
            $table->dropColumn([
                'actual_loading_time_at', 
                'actual_closing_time_at',
                'actual_berthing_time_at',
                'actual_unloading_start_time_at',
                'actual_unloading_end_time_at'
            ]);
        });
    }
};
