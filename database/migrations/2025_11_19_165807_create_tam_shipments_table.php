<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tam_shipments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipping_schedule_id')->constrained()->cascadeOnDelete();

            $table->string('vin', 100);
            $table->string('engine_no', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('color', 50)->nullable();

            $table->string('do_number', 100)->nullable();

            $table->string('status')->default('booking');

            $table->dateTime('gate_in_at')->nullable();
            $table->dateTime('loaded_at')->nullable();
            $table->dateTime('arrived_at')->nullable();
            $table->dateTime('delivered_at')->nullable();

            $table->integer('dwelling_days')->nullable();
            $table->integer('sailing_days')->nullable();
            $table->integer('dooring_days')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tam_shipments');
    }
};
