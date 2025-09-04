<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // A. Data Customer & Dokumen
            $table->string('pic_name', 100)->nullable();
            $table->string('pic_phone', 20)->nullable();
            $table->string('request_type', 20)->nullable();
            $table->string('doc_number', 64)->nullable();
            $table->string('priority', 16)->default('normal');
            $table->date('requested_at')->nullable();
            $table->jsonb('attachments')->nullable();

            // B. Informasi Rute & Moda
            $table->string('mode', 10)->nullable();
            $table->string('vessel_name', 100)->nullable();
            $table->string('voyage', 50)->nullable();
            $table->string('pol', 100)->nullable();
            $table->string('pod', 100)->nullable();
            $table->dateTimeTz('pickup_date')->nullable();
            $table->string('vehicle_type', 20)->nullable();
            $table->string('vehicle_plate', 20)->nullable();
            $table->string('driver_name', 100)->nullable();
            $table->string('driver_phone', 20)->nullable();

            // C. Konfirmasi
            $table->boolean('confirm_is_true')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn([
                'pic_name','pic_phone','request_type','doc_number','priority','requested_at','attachments',
                'mode','vessel_name','voyage','pol','pod','pickup_date',
                'vehicle_type','vehicle_plate','driver_name','driver_phone',
                'confirm_is_true',
            ]);
        });
    }
};
