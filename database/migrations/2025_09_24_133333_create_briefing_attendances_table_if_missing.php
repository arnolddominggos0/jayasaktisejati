<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('briefing_attendances')) {
            Schema::create('briefing_attendances', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('session_id')->constrained('briefing_sessions')->cascadeOnDelete();
                $table->foreignId('manpower_id')->constrained('manpowers')->cascadeOnDelete();

                $table->string('attendance_status', 32);
                $table->decimal('temperature', 4, 1)->nullable(); 
                $table->string('bp', 16)->nullable();             
                $table->boolean('has_ppe')->default(false);
                $table->text('remark')->nullable();

                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

                $table->timestamps();

                $table->unique(['session_id', 'manpower_id'], 'briefing_attendance_unique');

                $table->index('session_id', 'briefing_attendances_session_idx');
                $table->index('manpower_id', 'briefing_attendances_manpower_idx');
                $table->index('attendance_status', 'briefing_attendances_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('briefing_attendances');
    }
};
