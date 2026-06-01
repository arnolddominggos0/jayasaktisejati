<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ppe_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ppe_item_id')->constrained('ppe_items')->cascadeOnDelete();
            $table->foreignId('manpower_id')->constrained('manpowers')->cascadeOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['ppe_item_id', 'assigned_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('ppe_assignments');
    }
};
