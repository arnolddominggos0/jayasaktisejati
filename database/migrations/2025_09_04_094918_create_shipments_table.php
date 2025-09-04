<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();

            $table->string('code', 32)->unique()->index();

            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('origin_office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->foreignId('destination_office_id')->nullable()->constrained('offices')->nullOnDelete();

            

            $table->string('route_from', 128)->nullable();
            $table->string('route_to', 128)->nullable();
            $table->string('service_type', 32)->nullable(); 

            $table->string('status', 16);

            $table->dateTimeTz('eta')->nullable();
            $table->dateTimeTz('etd')->nullable();

            $table->text('notes')->nullable();
            $table->timestampsTz();
        });

        DB::statement("
            ALTER TABLE shipments
            ALTER COLUMN status TYPE shipment_status
            USING status::shipment_status
        ");
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
