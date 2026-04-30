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
        Schema::create('vessel_check_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vessel_check_case_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('request_type', 30);
            // ACCELERATION | RESCHEDULE | ALTERNATIVE_VESSEL

            $table->string('requested_to', 30);
            // TANTO | MERATUS | TAM

            $table->text('request_note')->nullable();

            $table->string('status', 20)->default('SENT');
            // SENT | CONFIRMED | REJECTED

            $table->text('response_note')->nullable();
            $table->timestamp('responded_at')->nullable();

            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vessel_check_requests');
    }
};
