<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'receiver_name')) {
                $table->dropColumn('receiver_name');
            }
            $table->string('receiver_name', 100)->nullable()->after('receiver_id');

            if (!Schema::hasColumn('shipments', 'origin_city_id')) {
                $table->foreignId('origin_city_id')->nullable()->constrained('cities')->nullOnDelete()->after('destination_office_id');
            }
            if (!Schema::hasColumn('shipments', 'destination_city_id')) {
                $table->foreignId('destination_city_id')->nullable()->constrained('cities')->nullOnDelete()->after('origin_city_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'receiver_name')) {
                $table->dropColumn('receiver_name');
            }
            if (Schema::hasColumn('shipments', 'origin_city_id')) {
                $table->dropConstrainedForeignId('origin_city_id');
            }
            if (Schema::hasColumn('shipments', 'destination_city_id')) {
                $table->dropConstrainedForeignId('destination_city_id');
            }
        });
    }
};
