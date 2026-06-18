<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_health_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attendance_id')
                ->constrained('briefing_attendances')
                ->cascadeOnDelete();

            // Event classification
            // initial_check | auto_fit | auto_not_fit |
            // medical_action | recheck_started | recheck_fit | recheck_not_fit
            $table->string('event_type');

            // Vital snapshot at time of event (nullable — not all events have vitals)
            $table->decimal('temperature', 4, 1)->nullable();
            $table->smallInteger('bp_systolic')->nullable();
            $table->smallInteger('bp_diastolic')->nullable();

            // Medical context
            $table->string('medical_action')->nullable();

            // Free-form note
            $table->text('remark')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_health_logs');
    }
};
