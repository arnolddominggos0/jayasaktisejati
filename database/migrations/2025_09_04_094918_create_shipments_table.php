<?php

use App\Enums\{ShipmentStatus, ShipmentMode, ServiceType, CargoType};
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();

            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('receiver_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('receiver_name', 100)->nullable();

            $table->foreignId('origin_office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->foreignId('destination_office_id')->nullable()->constrained('offices')->nullOnDelete();

            // A. Data Customer & Dokumen
            $table->string('pic_name', 100)->nullable();
            $table->string('pic_phone', 30)->nullable();
            $table->string('request_type', 32)->nullable();
            $table->string('doc_number', 64)->nullable();
            $table->enum('priority', ['normal', 'urgent'])->default('normal');
            $table->json('attachments')->nullable();

            // B. Informasi Rute & Moda
            $table->string('mode', 16)->default(ShipmentMode::Sea->value);
            $table->string('route_from')->nullable();
            $table->string('route_to')->nullable();
            $table->string('route_summary')->nullable();

            // Layanan & Muatan
            $table->string('service_type', 32)->nullable();   
            $table->string('service_option', 32)->nullable(); 
            $table->string('cargo_type', 16)->nullable();     

            // FCL
            $table->string('container_size', 32)->nullable();
            $table->unsignedInteger('container_qty')->nullable();

            // LCL totals yang disimpan
            $table->unsignedInteger('packages_total')->nullable();
            $table->decimal('cbm_total', 10, 3)->nullable();
            $table->decimal('weight_total', 10, 2)->nullable(); 

            // Laut
            $table->string('vessel_name')->nullable();
            $table->string('voyage')->nullable();
            $table->string('pol')->nullable();
            $table->string('pod')->nullable();
            $table->dateTime('etd')->nullable();
            $table->dateTime('eta')->nullable();
            $table->foreignId('schedule_id')->nullable();

            // Darat
            $table->string('vehicle_type')->nullable();
            $table->string('vehicle_plate', 20)->nullable();
            $table->dateTime('pickup_date')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone', 30)->nullable();

            // Umum
            $table->string('status', 16)->default(ShipmentStatus::Draft->value);
            $table->text('notes')->nullable();

            // Perubahan & Konfirmasi
            $table->dateTime('requested_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('edited_fields')->nullable();
            $table->foreignId('last_edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('confirm_is_true')->default(false);

            // Lead Time
            $table->dateTime('estimated_ready_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
