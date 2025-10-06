<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (! Schema::hasColumn('shipments', 'cbm_total')) {
                $table->decimal('cbm_total', 10, 3)
                    ->nullable()
                    ->after('service_option');
            }

            if (! Schema::hasColumn('shipments', 'packages_total')) {
                $table->integer('packages_total')
                    ->nullable()
                    ->after('cbm_total');
            }
            if (! Schema::hasColumn('shipments', 'weight_total')) {
                $table->decimal('weight_total', 12, 2)
                    ->nullable()
                    ->after('packages_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'weight_total')) {
                $table->dropColumn('weight_total'); 
            }
            if (Schema::hasColumn('shipments', 'packages_total')) {
                $table->dropColumn('packages_total');
            }
            if (Schema::hasColumn('shipments', 'cbm_total')) {
                $table->dropColumn('cbm_total');
            }
        });
    }
};
