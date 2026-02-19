<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vessel_plans', function (Blueprint $table) {
            $table->id();

            $table->date('period_month'); 
            $table->string('route_code', 20)->default('JKT-BTG');

            $table->string('status', 20)->default('draft'); 

            $table->timestamp('finalized_at')->nullable();
            $table->unsignedBigInteger('finalized_by')->nullable();

            $table->text('note')->nullable();

            $table->timestamps();

            $table->unique(['period_month', 'route_code']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vessel_plans');
    }
};

