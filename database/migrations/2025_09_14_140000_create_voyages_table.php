<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('voyages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vessel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pol_id')->constrained('ports')->cascadeOnDelete();
            $table->foreignId('pod_id')->constrained('ports')->cascadeOnDelete();
            $table->string('voyage_no');
            $table->string('service')->nullable();
            $table->timestamp('etd');
            $table->timestamp('eta');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('voyages');
    }
};
