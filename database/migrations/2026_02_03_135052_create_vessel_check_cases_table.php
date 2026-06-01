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
        Schema::create('vessel_check_cases', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipping_schedule_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->boolean('delay_flag')->default(false);

            $table->string('case_status', 30)->default('ON_SCHEDULE');
            /*
            ON_SCHEDULE
            ETD_DELAY
            IN_PROGRESS
            RESOLVED
            COMPLETED
            */

            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vessel_check_cases');
    }
};
