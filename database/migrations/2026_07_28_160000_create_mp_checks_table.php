<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mp_checks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attendance_id')
                ->constrained('briefing_attendances')
                ->cascadeOnDelete();

            // initial | recheck
            $table->string('check_type');

            $table->decimal('temperature', 4, 1)->nullable();
            $table->smallInteger('bp_systolic')->nullable();
            $table->smallInteger('bp_diastolic')->nullable();

            // FIT | TIDAK FIT
            $table->string('status')->nullable();

            $table->text('health_complaint')->nullable();

            $table->timestamp('checked_at');
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['attendance_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mp_checks');
    }
};
