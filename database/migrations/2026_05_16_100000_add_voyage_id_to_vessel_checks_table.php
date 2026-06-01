<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vessel_checks', function (Blueprint $table) {
            if (! Schema::hasColumn('vessel_checks', 'voyage_id')) {
                $table->foreignId('voyage_id')->nullable()->after('shipping_schedule_id')->constrained('voyages')->nullOnDelete();
                $table->index('voyage_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vessel_checks', function (Blueprint $table) {
            if (Schema::hasColumn('vessel_checks', 'voyage_id')) {
                $table->dropForeign(['voyage_id']);
                $table->dropIndex(['voyage_id']);
                $table->dropColumn('voyage_id');
            }
        });
    }
};
