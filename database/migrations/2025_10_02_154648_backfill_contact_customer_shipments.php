<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'pickup_contact_name')) {
                $table->string('pickup_contact_name', 100)->nullable()->after('receiver_id');
            }
            if (!Schema::hasColumn('shipments', 'pickup_contact_phone')) {
                $table->string('pickup_contact_phone', 30)->nullable()->after('pickup_contact_name');
            }
            if (!Schema::hasColumn('shipments', 'delivery_contact_name')) {
                $table->string('delivery_contact_name', 100)->nullable()->after('pickup_contact_phone');
            }
            if (!Schema::hasColumn('shipments', 'delivery_contact_phone')) {
                $table->string('delivery_contact_phone', 30)->nullable()->after('delivery_contact_name');
            }
        });

        if (Schema::hasColumn('shipments', 'pic_name') && Schema::hasColumn('shipments', 'pic_phone')) {
            DB::statement("
                UPDATE shipments
                SET delivery_contact_name  = COALESCE(delivery_contact_name, pic_name),
                    delivery_contact_phone = COALESCE(delivery_contact_phone, pic_phone)
                WHERE delivery_contact_name IS NULL OR delivery_contact_phone IS NULL
            ");
        }
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'delivery_contact_phone')) $table->dropColumn('delivery_contact_phone');
            if (Schema::hasColumn('shipments', 'delivery_contact_name'))  $table->dropColumn('delivery_contact_name');
            if (Schema::hasColumn('shipments', 'pickup_contact_phone'))   $table->dropColumn('pickup_contact_phone');
            if (Schema::hasColumn('shipments', 'pickup_contact_name'))    $table->dropColumn('pickup_contact_name');
        });
    }
};
