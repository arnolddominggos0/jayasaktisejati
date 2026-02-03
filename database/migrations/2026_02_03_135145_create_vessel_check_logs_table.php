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
        Schema::create('vessel_check_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vessel_check_case_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('check_date');
            $table->string('day_code', 5); // D-3, D-2, D-1

            $table->dateTime('etd_plan')->nullable();
            $table->dateTime('etd_current')->nullable();

            $table->string('status', 30)->default('on_schedule');
            // on_schedule | potential_delay | delayed

            $table->string('source')->nullable(); // WA / Tantolink / Manual
            $table->text('note')->nullable();

            $table->foreignId('created_by')->nullable();
            $table->timestamps();

            $table->unique([
                'vessel_check_case_id',
                'check_date',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vessel_check_logs');
    }
};
