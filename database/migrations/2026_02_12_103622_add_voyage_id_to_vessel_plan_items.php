<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vessel_plan_items', function (Blueprint $table) {

            $table->foreignId('voyage_id')
                ->nullable()
                ->after('vessel_id')
                ->constrained('voyages')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vessel_plan_items', function (Blueprint $table) {
            $table->dropForeign(['voyage_id']);
            $table->dropColumn('voyage_id');
        });
    }
};
