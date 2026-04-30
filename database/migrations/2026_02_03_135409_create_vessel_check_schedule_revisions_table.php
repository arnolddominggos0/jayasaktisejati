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
        Schema::create('vessel_check_schedule_revisions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vessel_check_case_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('old_voyage_id')->constrained('voyages');
            $table->foreignId('new_voyage_id')->constrained('voyages');

            $table->dateTime('old_etd');
            $table->dateTime('new_etd');

            $table->text('revision_note')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vessel_check_schedule_revisions');
    }
};
