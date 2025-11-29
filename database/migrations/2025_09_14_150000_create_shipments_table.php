<?php

use App\Enums\{ShipmentStatus, ShipmentMode};
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

            $table->foreignId('origin_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('destination_city_id')->nullable()->constrained('cities')->nullOnDelete();

            $table->foreignId('origin_office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->foreignId('destination_office_id')->nullable()->constrained('offices')->nullOnDelete();

            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();

            $table->string('pic_name', 100)->nullable();
            $table->string('pic_phone', 30)->nullable();

            $table->string('pickup_contact_name', 100)->nullable();
            $table->string('pickup_contact_phone', 30)->nullable();
            $table->string('delivery_contact_name', 100)->nullable();
            $table->string('delivery_contact_phone', 30)->nullable();

            $table->string('request_type', 32)->nullable();
            $table->string('doc_number', 64)->nullable();
            $table->enum('priority', ['normal', 'urgent'])->default('normal');
            $table->json('attachments')->nullable();

            $table->string('mode', 16)->default(ShipmentMode::Sea->value);
            $table->string('route_from')->nullable();
            $table->string('route_to')->nullable();
            $table->string('route_summary')->nullable();

            $table->string('service_type', 32)->nullable();
            $table->string('service_option', 32)->nullable();
            $table->string('delivery_scope', 32)->nullable();
            $table->string('cargo_type', 16)->nullable();

            $table->string('container_size', 32)->nullable();
            $table->unsignedInteger('container_qty')->nullable();
            $table->string('container_no', 64)->nullable();
            $table->string('seal_no', 64)->nullable();

            $table->unsignedInteger('packages_total')->nullable();
            $table->decimal('cbm_total', 10, 3)->nullable();
            $table->decimal('weight_total', 10, 2)->nullable();

            $table->string('vessel_name')->nullable();
            $table->string('voyage')->nullable();
            $table->string('pol')->nullable();
            $table->string('pod')->nullable();
            $table->dateTime('etd')->nullable();
            $table->dateTime('eta')->nullable();

            $table->foreignId('voyage_id')->nullable()->constrained('voyages')->nullOnDelete();
            $table->unsignedBigInteger('shipping_schedule_id')->nullable();
            
            $table->foreignId('assigned_depot_id')->nullable()->constrained('depots')->nullOnDelete();

            $table->string('vehicle_type')->nullable();
            $table->string('vehicle_plate', 20)->nullable();
            $table->dateTime('pickup_date')->nullable();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();

            $table->string('vehicle_kind', 50)->nullable();
            $table->string('vehicle_loading', 50)->nullable();

            $table->string('status', 32)->default(ShipmentStatus::Draft->value);
            $table->text('notes')->nullable();

            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('requested_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();

            $table->json('edited_fields')->nullable();
            $table->foreignId('last_edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('confirm_is_true')->default(false);

            $table->dateTime('estimated_ready_at')->nullable();

            $table->json('containers')->nullable();
            $table->json('lcl_items')->nullable();
            $table->json('units')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
