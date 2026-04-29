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
        Schema::create('vessel_check_etd_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vessel_check_case_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->dateTime('etd_before')->nullable();
            $table->dateTime('etd_after')->nullable();
            $table->string('source')->nullable();
            $table->text('note')->nullable();

            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vessel_check_etd_logs');
    }
};
