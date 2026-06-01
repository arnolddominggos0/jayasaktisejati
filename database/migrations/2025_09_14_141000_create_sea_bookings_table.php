<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sea_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // internal booking code
            $table->foreignId('shipping_line_id')->constrained('shipping_lines')->cascadeOnDelete();
            $table->foreignId('voyage_id')->nullable()->constrained('voyages')->nullOnDelete();
            $table->string('ro_no')->nullable(); // Receiving Order
            $table->string('rc_no')->nullable(); // Receiving Code / Reference
            $table->string('si_no')->nullable(); // Shipping Instruction No
            $table->string('status')->default('draft'); // enum string
            $table->foreignId('depot_id')->nullable()->constrained('depots')->nullOnDelete(); // lokasi operasi/koordinator
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['shipping_line_id','status']);
        });
    }
    public function down(): void { Schema::dropIfExists('sea_bookings'); }
};
