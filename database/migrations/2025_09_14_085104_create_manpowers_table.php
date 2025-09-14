<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('manpowers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->default('sea_freight');
            $table->json('skills')->nullable();  
            $table->json('certs')->nullable();   
            $table->string('license_number')->nullable();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('depot_id')->constrained('depots')->cascadeOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['domain','depot_id','active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manpowers');
    }
};
