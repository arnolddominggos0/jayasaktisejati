<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_inspections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('unit_id')
                ->constrained('units')
                ->cascadeOnDelete();

            // Stage dalam journey unit — pickup s/d dooring
            $table->string('stage', 40);       // pickup | handover_depot | loading | unloading | selfdrive | dooring

            // Hasil pemeriksaan keseluruhan stage
            $table->string('status', 20);       // passed | failed

            // Asal data — live atau backfill historis
            $table->string('source', 40);       // live | historical_import

            // Waktu inspeksi dilakukan (aktual, bukan created_at)
            $table->timestamp('checked_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            // ── Indexes ────────────────────────────────────────────────────────
            $table->index('unit_id');
            $table->index('stage');
            $table->index('status');
            $table->index(['unit_id', 'stage']);   // composite — query per unit per stage
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_inspections');
    }
};
