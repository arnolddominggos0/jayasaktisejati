<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('container_no', 20)->nullable()->after('delivery_scope');
            $table->string('seal_no', 20)->nullable()->after('container_no');
            $table->index('container_no');
            $table->index('seal_no');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex(['container_no']);
            $table->dropIndex(['seal_no']);
            $table->dropColumn(['container_no', 'seal_no']);
        });
    }
};
