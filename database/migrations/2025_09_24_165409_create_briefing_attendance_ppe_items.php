<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('briefing_attendance_ppe_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('attendance_id')
                ->constrained('briefing_attendances')
                ->cascadeOnDelete();
            $t->string('ppe_type', 32);    
            $t->string('condition', 16);   
            $t->string('remark', 100)->nullable();
            $t->timestamps();

            $t->unique(['attendance_id', 'ppe_type'], 'attendance_ppe_unique');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('briefing_attendance_ppe_items');
    }
};
