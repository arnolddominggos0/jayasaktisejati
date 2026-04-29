<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'pickup_started_at')) $table->timestamp('pickup_started_at')->nullable()->after('requested_at');
            if (!Schema::hasColumn('shipments', 'onboard_at'))        $table->timestamp('onboard_at')->nullable()->after('pickup_started_at');
            if (!Schema::hasColumn('shipments', 'arrived_at'))        $table->timestamp('arrived_at')->nullable()->after('onboard_at');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'pickup_started_at')) $table->dropColumn('pickup_started_at');
            if (Schema::hasColumn('shipments', 'onboard_at'))        $table->dropColumn('onboard_at');
            if (Schema::hasColumn('shipments', 'arrived_at'))        $table->dropColumn('arrived_at');
        });
    }
};
