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
        Schema::create('vessel_check_delays', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vessel_check_case_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('delay_category', 30);
            /*WEATHER
            WEATHER
            PORT
            VESSEL
            OPERATIONAL
            OTHER
            */
            $table->text('delay_reason');
            $table->integer('delay_minutes')->nullable();
            $table->text('impact_description')->nullable();

            $table->date('analysis_date');
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vessel_check_delays');
    }
};
