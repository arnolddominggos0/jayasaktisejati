<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('shipment_tracks', 'planned_at')) {
            Schema::table('shipment_tracks', function (Blueprint $table) {
                $table->timestamp('planned_at')->nullable()->after('status');
            });
        }
        if (!Schema::hasColumn('shipment_tracks', 'actual_at')) {
            Schema::table('shipment_tracks', function (Blueprint $table) {
                $table->timestamp('actual_at')->nullable()->after('planned_at');
            });
        }
        if (!Schema::hasColumn('shipment_tracks', 'remarks')) {
            Schema::table('shipment_tracks', function (Blueprint $table) {
                $table->text('remarks')->nullable()->after('location');
            });
        }
        if (!Schema::hasColumn('shipment_tracks', 'note')) {
            Schema::table('shipment_tracks', function (Blueprint $table) {
                $table->text('note')->nullable()->after('remarks');
            });
        }
    }

    public function down(): void
    {
        Schema::table('shipment_tracks', function (Blueprint $table) {
            if (Schema::hasColumn('shipment_tracks', 'planned_at')) $table->dropColumn('planned_at');
            if (Schema::hasColumn('shipment_tracks', 'actual_at'))  $table->dropColumn('actual_at');
            if (Schema::hasColumn('shipment_tracks', 'remarks'))    $table->dropColumn('remarks');
            if (Schema::hasColumn('shipment_tracks', 'note'))       $table->dropColumn('note');
        });
    }
};
