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
            $table->string('size_type');
            $table->string('container_no')->nullable(); 
            $table->string('seal_no')->nullable();
            $table->string('status')->default('reserved');
            $table->integer('gross_weight')->nullable(); 
            $table->timestamps();

            $table->index(['booking_id','status']);
            $table->unique(['container_no']); 
        });
    }
    public function down(): void { Schema::dropIfExists('sea_containers'); }
};
