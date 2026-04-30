<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vessel_plan_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vessel_plan_id')->constrained()->cascadeOnDelete();
            $table->string('action', 32);
            $table->text('note')->nullable();
            $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acted_at');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['vessel_plan_id', 'action']);
            $table->index('acted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vessel_plan_reviews');
    }
};
