<?php

use App\Enums\PpeCondition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ppe_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('briefing_attendance_id')->constrained('briefing_attendances')->cascadeOnDelete();
            $table->foreignId('ppe_item_id')->nullable()->constrained('ppe_items')->nullOnDelete();
            $table->string('type');
            $table->string('condition');
            $table->string('remark')->nullable();
            $table->timestamps();
            $table->unique(['briefing_attendance_id', 'type']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('ppe_inspections');
    }
};
