<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('shipping_schedules', 'vessel_name')) {
            Schema::table('shipping_schedules', function (Blueprint $table) {
                $table->dropColumn('vessel_name');
            });
        }
    }


    public function down(): void
    {
        if (! Schema::hasColumn('shipping_schedules', 'vessel_name')) {
            Schema::table('shipping_schedules', function (Blueprint $table) {
                $table->string('vessel_name')->nullable();
            });
        }
    }
};
