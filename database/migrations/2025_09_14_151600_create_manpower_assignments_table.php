<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('manpower_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manpower_id')->constrained('manpowers')->cascadeOnDelete();
            $table->foreignId('shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('depot_id')->nullable()->constrained('depots')->nullOnDelete();
            $table->date('date');
            $table->string('role_at_task')->nullable(); 
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['date','depot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manpower_assignments');
    }
};
