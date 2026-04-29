<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('briefing_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('briefing_sessions')->cascadeOnDelete();
            $table->string('item');   
            $table->string('status');
            $table->string('remark')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('briefing_checklists');
    }
};
