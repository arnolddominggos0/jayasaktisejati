<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vessel_check_evaluations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vessel_check_case_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('final_status', 30);
            $table->integer('total_delay_minutes')->nullable();
            $table->text('resolution_summary')->nullable();

            $table->foreignId('evaluated_by')->nullable();
            $table->timestamp('evaluated_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vessel_check_evaluations');
    }
};
