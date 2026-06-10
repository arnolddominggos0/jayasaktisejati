<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel histori jadwal voyage — untuk Schedule History Logbook TAM.
 *
 * Tiga schedule_type per voyage:
 *
 *   'draft'  — jadwal saat draft Vessel Plan dikirim ke customer.
 *              ETD/ETA diambil dari VesselPlanSnapshot(draft_submitted).
 *              Disimpan saat finalisasi (voyage belum ada saat draft dikirim).
 *
 *   'final'  — jadwal yang disetujui saat Vessel Plan difinalisasi.
 *              = voyage.etd / voyage.eta pada saat finalisasi.
 *
 *   'actual' — waktu keberangkatan dan tiba aktual.
 *              = voyage.atd_at / voyage.ata_at.
 *              Di-auto-create saat voyage mempunyai kedua nilai tsb.
 *              (Actual JUGA disimpan di sini supaya variance bisa dihitung
 *               tanpa query voyage langsung dari blade.)
 *
 * sailing_days disimpan agar variance bisa dihitung tanpa recalculate.
 *
 * captured_at / captured_by — waktu dan aktor yang mencatat jadwal ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voyage_schedule_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('voyage_id')
                ->constrained('voyages')
                ->cascadeOnDelete();

            // draft | final | actual
            $table->string('schedule_type', 20);

            $table->timestamp('etd')->nullable()->comment('ETD (draft/final) atau ATD (actual)');
            $table->timestamp('eta')->nullable()->comment('ETA (draft/final) atau ATA (actual)');

            $table->decimal('sailing_days', 8, 2)->nullable()
                ->comment('Selisih etd→eta dalam hari, dihitung dan disimpan saat pencatatan');

            $table->text('notes')->nullable();

            // Waktu snapshot diambil (bisa berbeda dari created_at)
            $table->timestamp('captured_at')->nullable();
            $table->string('captured_by')->nullable();

            $table->timestamps();

            // Maksimal satu baris per type per voyage
            $table->unique(['voyage_id', 'schedule_type']);
            $table->index('voyage_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voyage_schedule_histories');
    }
};
