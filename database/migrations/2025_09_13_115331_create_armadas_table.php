<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('armadas', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('type', ['truck','cc_tw','container','kapal']);
            $table->string('plate_number')->nullable(); // untuk truck
            $table->string('name')->nullable(); // nama kapal atau identitas
            $table->integer('capacity')->nullable();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('manpowers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('role', ['driver','helper','operator','admin']);
            $table->string('phone')->nullable();
            $table->string('license_number')->nullable(); // SIM / sertifikat
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('ship_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('armada_id')->constrained('armadas')->cascadeOnDelete();
            $table->dateTime('departure_time');
            $table->dateTime('arrival_time')->nullable();
            $table->string('origin_port');
            $table->string('destination_port');
            $table->string('voyage_number')->nullable();
            $table->timestamps();
        });

        Schema::create('armada_manpower', function (Blueprint $table) {
            $table->id();
            $table->foreignId('armada_id')->constrained()->cascadeOnDelete();
            $table->foreignId('manpower_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('armada_manpower');
        Schema::dropIfExists('ship_schedules');
        Schema::dropIfExists('manpowers');
        Schema::dropIfExists('armadas');
    }
};
