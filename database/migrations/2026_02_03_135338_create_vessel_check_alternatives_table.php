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
        Schema::create('vessel_check_alternatives', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vessel_check_case_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('alt_vessel_id')
                ->constrained('vessels');

            $table->foreignId('alt_voyage_id')
                ->constrained('voyages');

            $table->dateTime('alt_etd');
            $table->text('proposal_note')->nullable();

            $table->string('approval_status', 20)->default('PENDING');
            // PENDING | APPROVED | REJECTED

            $table->foreignId('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vessel_check_alternatives');
    }
};
