<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('depots', function (Blueprint $table) {
            if (!Schema::hasColumn('depots', 'mode')) {
                $table->string('mode', 20)->default('sea')->after('name'); 
            }

            if (!Schema::hasColumn('depots', 'port_id')) {
                $table->foreignId('port_id')
                      ->nullable()
                      ->constrained('ports')
                      ->nullOnDelete()
                      ->after('mode');
            }

            if (!Schema::hasColumn('depots', 'service_types')) {
                $table->json('service_types')->nullable()->after('port_id');
            }

        });
    }

    public function down(): void
    {
        Schema::table('depots', function (Blueprint $table) {
            if (Schema::hasColumn('depots', 'service_types')) {
                $table->dropColumn('service_types');
            }
            if (Schema::hasColumn('depots', 'port_id')) {
                $table->dropConstrainedForeignId('port_id');
            }
            if (Schema::hasColumn('depots', 'mode')) {
                $table->dropColumn('mode');
            }
        });
    }
};
