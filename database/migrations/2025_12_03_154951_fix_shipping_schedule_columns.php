<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_schedules', function (Blueprint $table) {

            $drop = [
                'shipping_line_id',
                'vessel_id',
                'pol_id',
                'pod_id',
                'voyage_no',
            ];

            foreach ($drop as $col) {
                if (Schema::hasColumn('shipping_schedules', $col)) {
                    $table->dropColumn($col);
                }
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
