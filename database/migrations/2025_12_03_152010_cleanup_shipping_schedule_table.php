<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('shipping_schedules', 'shipping_line_id')) {
                $table->dropColumn('shipping_line_id');
            }

            if (Schema::hasColumn('shipping_schedules', 'vessel_id')) {
                $table->dropColumn('vessel_id');
            }

            if (Schema::hasColumn('shipping_schedules', 'pol_id')) {
                $table->dropColumn('pol_id');
            }

            if (Schema::hasColumn('shipping_schedules', 'pod_id')) {
                $table->dropColumn('pod_id');
            }

            if (Schema::hasColumn('shipping_schedules', 'voyage_no')) {
                $table->dropColumn('voyage_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipping_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('shipping_line_id')->nullable();
            $table->unsignedBigInteger('vessel_id')->nullable();
            $table->unsignedBigInteger('pol_id')->nullable();
            $table->unsignedBigInteger('pod_id')->nullable();
            $table->string('voyage_no')->nullable();
        });
    }
};
