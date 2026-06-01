<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('armada_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('armada_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('planned_at')->nullable();
            $table->date('done_at')->nullable();
            $table->integer('odometer')->nullable();
            $table->bigInteger('cost')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('armada_maintenances');
    }
};
