<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropForeign(['assigned_depot_id']);
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->foreign('assigned_depot_id')
                ->references('id')->on('depots')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropForeign(['assigned_depot_id']);
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->foreign('assigned_depot_id')
                ->references('id')->on('depots')
                ->nullOnDelete();
        });
    }
};
