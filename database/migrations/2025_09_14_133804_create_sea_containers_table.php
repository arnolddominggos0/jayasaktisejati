<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sea_containers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('sea_bookings')->cascadeOnDelete();
            $table->string('size_type'); // enum string
            $table->string('container_no')->nullable(); // diisi saat sudah assign
            $table->string('seal_no')->nullable();
            $table->string('status')->default('reserved'); // enum ContainerStatus
            $table->integer('gross_weight')->nullable(); // kg
            $table->timestamps();

            $table->index(['booking_id','status']);
            $table->unique(['container_no']); // biar unik jika sudah ada
        });
    }
    public function down(): void { Schema::dropIfExists('sea_containers'); }
};
