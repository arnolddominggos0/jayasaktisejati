<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('manpower_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('briefing_sessions')->cascadeOnDelete();
            $table->foreignId('manpower_id')->constrained('manpowers')->cascadeOnDelete();
            $table->string('attendance_status');
            $table->decimal('temperature', 4, 1)->nullable();
            $table->string('bp')->nullable();
            $table->boolean('has_ppe')->default(false);
            $table->string('remark')->nullable();
            $table->timestamps();

            $table->unique(['session_id','manpower_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manpower_attendances');
    }
};
