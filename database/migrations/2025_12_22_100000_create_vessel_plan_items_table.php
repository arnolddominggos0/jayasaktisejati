<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vessel_plan_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vessel_plan_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('shipping_line_id')->constrained();
            $table->foreignId('vessel_id')->constrained();

            $table->timestamp('planned_etd');
            $table->timestamp('planned_eta')->nullable();

            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['vessel_plan_id', 'planned_etd']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vessel_plan_items');
    }
};
