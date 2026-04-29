<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (! Schema::hasColumn('shipments', 'container_no')) {
                $table->string('container_no', 64)->nullable();
            }

            if (! Schema::hasColumn('shipments', 'seal_no')) {
                $table->string('seal_no', 64)->nullable();
            }

            if (! Schema::hasColumn('shipments', 'container_size')) {
                $table->string('container_size', 32)->nullable();
            }

            if (! Schema::hasColumn('shipments', 'container_qty')) {
                $table->unsignedInteger('container_qty')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'container_no')) {
                $table->dropColumn('container_no');
            }

            if (Schema::hasColumn('shipments', 'seal_no')) {
                $table->dropColumn('seal_no');
            }

            if (Schema::hasColumn('shipments', 'container_size')) {
                $table->dropColumn('container_size');
            }

            if (Schema::hasColumn('shipments', 'container_qty')) {
                $table->dropColumn('container_qty');
            }
        });
    }
};
