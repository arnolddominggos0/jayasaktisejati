<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->foreignId('voyage_id')->nullable()->after('eta');
        });
        Schema::table('shipments', function (Blueprint $table) {
            $table->foreign('voyage_id')->references('id')->on('voyages')->nullOnDelete();
        });
        Schema::table('voyages', function (Blueprint $table) {
            $table->index('etd');
            $table->index('voyage_no');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropForeign(['voyage_id']);
            $table->dropColumn('voyage_id');
        });
        Schema::table('voyages', function (Blueprint $table) {
            $table->dropIndex(['etd']);
            $table->dropIndex(['voyage_no']);
        });
    }
};
