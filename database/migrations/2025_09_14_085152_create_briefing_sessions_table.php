<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('briefing_sessions', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('depot_id')->constrained('depots')->cascadeOnDelete();
            $table->foreignId('coordinator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['date','depot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('briefing_sessions');
    }
};
