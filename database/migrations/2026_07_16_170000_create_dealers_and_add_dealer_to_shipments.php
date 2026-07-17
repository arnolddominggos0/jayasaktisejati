<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DOMAIN-02 — Vehicle Shipment Domain Refinement (additive only).
 *
 * Dealer = jaringan distribusi milik Commercial Customer (Vehicle Shipment).
 * shipments.dealer_id + pickup_location nullable — General Cargo dan seluruh
 * kontrak existing (receiver_id, waybill, API, monitoring) tidak tersentuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('name', 150);
            // Alias pencocokan OCR: ["PT. HA KOTAMOBAGU", "HASJRAT ABADI", ...]
            $table->json('aliases')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('customer_id');
            $table->index('name');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->foreignId('dealer_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('dealers')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('pickup_location', 150)->nullable()->after('dealer_id');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dealer_id');
            $table->dropColumn('pickup_location');
        });

        Schema::dropIfExists('dealers');
    }
};
