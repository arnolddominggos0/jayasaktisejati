<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Voyage;
use App\Models\VesselCheck;
use Carbon\Carbon;

/**
 * Vessel Check — Carrier Readiness Monitoring
 *
 * Scope:
 *   Semua voyage yang akan berangkat dalam window H-2 / H-1 (berdasarkan ETD),
 *   terlepas dari apakah voyage tersebut memiliki shipment TAM atau tidak.
 *
 * Alasan bisnis:
 *   Vessel Check menjawab pertanyaan:
 *     "Apakah carrier siap berangkat sesuai jadwal?"
 *   Ini adalah pertanyaan operasional terhadap shipping line — bukan terhadap cargo TAM.
 *   Backup vessel, voyage kosong, dan voyage tanpa shipment TAM tetap perlu dipantau
 *   keberangkatannya sebagai bagian dari carrier relationship management.
 *
 * PERBEDAAN DENGAN MONITORING KAPAL TAM:
 *   Monitoring Kapal TAM menjawab: "Apakah cargo TAM berjalan sesuai target?"
 *   Source: Voyage::whereHas('shipments')
 *   Scope: hanya voyage yang memiliki shipment aktif
 *
 *   JANGAN menambahkan whereHas('shipments') pada command ini.
 *   Kedua modul memiliki scope berbeda secara disengaja.
 */
class GenerateDailyVesselChecks extends Command
{
    protected $signature = 'vessel-check:generate-daily';

    protected $description = 'Generate H-2 and H-1 vessel check placeholders for upcoming voyages (carrier readiness scope — all voyages)';

    public function handle(): int
    {
        $today = today();

        // Carrier readiness scope: all voyages in H-2/H-1 window, regardless of shipments.
        // See class PHPDoc for the business rationale.
        $voyages = Voyage::query()
            ->whereBetween('etd', [
                $today->copy()->addDays(1)->startOfDay(),
                $today->copy()->addDays(2)->endOfDay(),
            ])
            ->whereNull('atd_at')
            ->get();

        foreach ($voyages as $voyage) {
            $etd  = Carbon::parse($voyage->etd);
            $diff = (int) $today->diffInDays($etd, false);

            if (! in_array($diff, [1, 2])) {
                continue;
            }

            $dayCode = "H-{$diff}";

            // Create placeholder — status defaults to 'ok', operator changes to 'late' if needed.
            VesselCheck::firstOrCreate(
                [
                    'voyage_id'  => $voyage->id,
                    'check_date' => $today,
                ],
                [
                    'day_code' => $dayCode,
                    'status'   => 'ok',
                    'note'     => 'Auto-generated — harap konfirmasi status',
                ]
            );
        }

        $this->info('Daily vessel checks generated.');

        return self::SUCCESS;
    }
}
