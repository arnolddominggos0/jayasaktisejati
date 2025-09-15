<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('voyages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vessel_id')->constrained('vessels')->cascadeOnDelete();
            $table->foreignId('shipping_line_id')->constrained('shipping_lines')->cascadeOnDelete();
            $table->string('voyage_no'); // mis: 123N / 456S
            $table->foreignId('port_from_id')->constrained('ports')->cascadeOnDelete();
            $table->foreignId('port_to_id')->constrained('ports')->cascadeOnDelete();
            $table->date('etd');
            $table->date('eta')->nullable();
            $table->string('service')->nullable(); 
            $table->timestamps();

            $table->index(['shipping_line_id','voyage_no']);
        });
    }
    public function down(): void { Schema::dropIfExists('voyages'); }
};
