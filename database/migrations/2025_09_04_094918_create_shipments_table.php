<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();

            // Identitas
            $table->string('code', 32)->unique()->index();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('origin_office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->foreignId('destination_office_id')->nullable()->constrained('offices')->nullOnDelete();

            // Dokumen
            $table->string('pic_name', 100)->nullable();
            $table->string('pic_phone', 20)->nullable();
            $table->string('request_type', 20)->nullable();
            $table->string('doc_number', 64)->nullable();
            $table->string('priority', 16)->default('normal');
            $table->date('requested_at')->nullable();
            $table->jsonb('attachments')->nullable();

            // Rute
            $table->string('route_from', 128)->nullable();
            $table->string('route_to', 128)->nullable();
            $table->string('route_summary', 192)->nullable();

            // Mode & layanan (awal: string biasa)
            $table->string('mode', 10)->nullable();            
            $table->string('service_type', 32)->nullable();    
            $table->string('service_option', 20)->nullable();  
            $table->string('cargo_type', 16)->nullable();      

            // Laut
            $table->string('vessel_name', 100)->nullable();
            $table->string('voyage', 50)->nullable();
            $table->string('pol', 100)->nullable();
            $table->string('pod', 100)->nullable();
            $table->dateTimeTz('etd')->nullable();
            $table->dateTimeTz('eta')->nullable();

            // Darat
            $table->string('vehicle_type', 20)->nullable();
            $table->string('vehicle_plate', 20)->nullable();
            $table->dateTimeTz('pickup_date')->nullable();
            $table->string('driver_name', 100)->nullable();
            $table->string('driver_phone', 20)->nullable();

            $table->string('status', 16);

            $table->text('notes')->nullable();
            $table->boolean('confirm_is_true')->default(false);

            $table->timestampsTz();

            $table->index(['status']);
            $table->index(['service_type']);
            $table->index(['origin_office_id']);
            $table->index(['destination_office_id']);
        });

        DB::statement("ALTER TABLE shipments ALTER COLUMN status DROP DEFAULT;");
        DB::statement("ALTER TABLE shipments ALTER COLUMN status TYPE shipment_status USING status::shipment_status;");
        DB::statement("ALTER TABLE shipments ALTER COLUMN status SET DEFAULT 'draft';");

        DB::statement("ALTER TABLE shipments ALTER COLUMN mode TYPE shipment_mode USING mode::shipment_mode;");
        DB::statement("ALTER TABLE shipments ALTER COLUMN service_type TYPE service_type USING service_type::service_type;");
        DB::statement("ALTER TABLE shipments ALTER COLUMN cargo_type TYPE cargo_type USING cargo_type::cargo_type;");
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
