<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->foreignId('container_id')
                ->nullable()
                ->after('shipment_id')
                ->constrained('containers')
                ->nullOnDelete();

            $table->string('allocation_status', 30)
                ->default('not_in_container')
                ->after('container_id');
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropConstrainedForeignId('container_id');
            $table->dropColumn('allocation_status');
        });
    }
};
