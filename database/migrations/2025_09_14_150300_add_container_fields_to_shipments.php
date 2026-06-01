<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('shipments', function (Blueprint $t) {
            if (!Schema::hasColumn('shipments','container_size')) $t->string('container_size', 32)->nullable()->after('service_option'); 
            if (!Schema::hasColumn('shipments','container_qty'))  $t->unsignedInteger('container_qty')->nullable()->after('container_size');
        });
    }
    public function down(): void {
        Schema::table('shipments', function (Blueprint $t) {
            if (Schema::hasColumn('shipments','container_qty'))  $t->dropColumn('container_qty');
            if (Schema::hasColumn('shipments','container_size')) $t->dropColumn('container_size');
        });
    }
};
