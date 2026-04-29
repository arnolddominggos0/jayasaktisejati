<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'vehicle_kind')) {
                $table->string('vehicle_kind')->nullable()->after('vehicle_plate');
            }
            if (!Schema::hasColumn('shipments', 'vehicle_loading')) {
                $table->string('vehicle_loading')->nullable()->after('vehicle_kind');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'vehicle_loading')) {
                $table->dropColumn('vehicle_loading');
            }
            if (Schema::hasColumn('shipments', 'vehicle_kind')) {
                $table->dropColumn('vehicle_kind');
            }
        });
    }
};
