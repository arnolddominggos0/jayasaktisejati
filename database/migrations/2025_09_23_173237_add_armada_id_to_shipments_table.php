<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'armada_id')) {
                $table->foreignId('armada_id')
                      ->nullable()
                      ->constrained('armadas')
                      ->nullOnDelete()
                      ->after('voyage_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'armada_id')) {
                $table->dropConstrainedForeignId('armada_id');
            }
        });
    }
};
