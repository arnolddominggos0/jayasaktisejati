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
        Schema::table('vessel_plan_items', function (Blueprint $table) {
            if (Schema::hasColumn('vessel_plan_items', 'voyage_no')) {
                $table->dropColumn('voyage_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vessel_plan_items', function (Blueprint $table) {
            $table->string('voyage_no', 50)->nullable();
        });
    }
};
