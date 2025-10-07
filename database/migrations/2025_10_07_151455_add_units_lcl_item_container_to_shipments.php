<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'containers')) {
                $table->json('containers')->nullable();
            }
            if (!Schema::hasColumn('shipments', 'lcl_items')) {
                $table->json('lcl_items')->nullable();
            }
            if (!Schema::hasColumn('shipments', 'units')) {
                $table->json('units')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'containers')) {
                $table->dropColumn('containers');
            }
            if (Schema::hasColumn('shipments', 'lcl_items')) {
                $table->dropColumn('lcl_items');
            }
            if (Schema::hasColumn('shipments', 'units')) {
                $table->dropColumn('units');
            }
        });
    }
};
