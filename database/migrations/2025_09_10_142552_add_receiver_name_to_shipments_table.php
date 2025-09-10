// database/migrations/2025_09_10_000001_add_receiver_id_to_shipments_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (! Schema::hasColumn('shipments', 'receiver_id')) {
                $table->foreignId('receiver_id')
                    ->nullable()
                    ->constrained('customers')
                    ->nullOnDelete()
                    ->after('customer_id');
            }

            if (Schema::hasColumn('shipments', 'receiver_name')) {
                $table->dropColumn('receiver_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'receiver_id')) {
                $table->dropConstrainedForeignId('receiver_id');
            }
        });
    }
};
