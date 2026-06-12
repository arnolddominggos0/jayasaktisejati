<?php

namespace App\Console\Commands;

use App\Models\Shipment;
use App\Models\Unit;
use App\Models\UnitInspection;
use App\Models\Voyage;
use App\Services\UnitInspectionGenerator;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

/**
 * Artisan command untuk generate historical inspection.
 *
 * Usage:
 *   php artisan inspections:generate-historical --voyage=1
 *   php artisan inspections:generate-historical --shipment=9
 *   php artisan inspections:generate-historical --unit=5
 *   php artisan inspections:generate-historical --all
 *   php artisan inspections:generate-historical --voyage=1 --dry-run
 *   php artisan inspections:generate-historical --voyage=1 --stages=pickup,loading,unloading,dooring
 */
class GenerateHistoricalInspections extends Command
{
    protected $signature = 'inspections:generate-historical
        {--unit=      : ID unit spesifik}
        {--shipment=  : ID shipment — generate semua unit dalam shipment}
        {--voyage=    : ID voyage — generate semua unit dalam voyage}
        {--all        : Generate semua unit yang ada}
        {--stages=    : Stages yang dipakai, dipisah koma (default: semua 6 stages)}
        {--dry-run    : Preview tanpa membuat data}';

    protected $description = 'Generate historical inspection records untuk unit (backfill historis)';

    // Default: full journey
    private const DEFAULT_STAGES = [
        'pickup',
        'handover_depot',
        'loading',
        'unloading',
        'selfdrive',
        'dooring',
    ];

    public function handle(UnitInspectionGenerator $generator): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        // ── Resolve stages ─────────────────────────────────────────────────────
        $stages = $this->resolveStages();
        if ($stages === null) {
            return self::FAILURE;
        }

        // ── Resolve units ──────────────────────────────────────────────────────
        $units = $this->resolveUnits();
        if ($units === null) {
            return self::FAILURE;
        }

        $this->info('');
        $this->info('╔══════════════════════════════════════════════╗');
        $this->info('║  Unit Inspection — Historical Generator      ║');
        $this->info('╚══════════════════════════════════════════════╝');
        $this->newLine();

        if ($isDryRun) {
            $this->warn('DRY RUN — tidak ada data yang akan dibuat.');
            $this->newLine();
        }

        $this->line('Stages  : ' . implode(' → ', $stages));
        $this->line('Units   : ' . $units->count());
        $this->line('Mode    : ' . ($isDryRun ? 'DRY RUN' : 'LIVE IMPORT'));
        $this->newLine();

        // ── Preview / dry-run ──────────────────────────────────────────────────
        if ($isDryRun) {
            return $this->runDryRun($generator, $units, $stages);
        }

        // ── Confirm sebelum insert ─────────────────────────────────────────────
        if (! $this->confirm("Lanjutkan generate {$units->count()} unit × " . count($stages) . " stages?")) {
            $this->warn('Dibatalkan.');
            return self::SUCCESS;
        }

        // ── Run ────────────────────────────────────────────────────────────────
        $bar = $this->output->createProgressBar($units->count());
        $bar->start();

        $totalInspections = 0;
        $totalItems       = 0;
        $totalSkipped     = 0;

        foreach ($units as $unit) {
            $result = $generator->generateHistorical($unit, $stages);
            $totalInspections += $result['inspections_created'];
            $totalItems       += $result['items_created'];
            $totalSkipped     += count($result['skipped_stages']);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // ── Summary ────────────────────────────────────────────────────────────
        $this->info('✓ Selesai!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Units processed',     $units->count()],
                ['Inspections created', $totalInspections],
                ['Items created',       $totalItems],
                ['Stages skipped',      $totalSkipped],
            ]
        );

        return self::SUCCESS;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resolveStages(): ?array
    {
        $stagesOption = $this->option('stages');

        if (! $stagesOption) {
            return self::DEFAULT_STAGES;
        }

        $requested = array_map('trim', explode(',', $stagesOption));
        $valid     = array_intersect($requested, UnitInspection::STAGES);
        $invalid   = array_diff($requested, UnitInspection::STAGES);

        if ($invalid) {
            $this->error('Stage tidak valid: ' . implode(', ', $invalid));
            $this->line('Stage yang tersedia: ' . implode(', ', UnitInspection::STAGES));
            return null;
        }

        return array_values($valid);
    }

    private function resolveUnits(): ?Collection
    {
        $unitId    = $this->option('unit');
        $shipmentId = $this->option('shipment');
        $voyageId  = $this->option('voyage');
        $all       = $this->option('all');

        // Exactly one scope harus dipilih
        $chosen = array_filter([$unitId, $shipmentId, $voyageId, $all]);
        if (count($chosen) === 0) {
            $this->error('Pilih salah satu: --unit, --shipment, --voyage, atau --all');
            return null;
        }

        if (count($chosen) > 1) {
            $this->error('Hanya satu scope yang boleh dipilih sekaligus.');
            return null;
        }

        // ── Scope: single unit ─────────────────────────────────────────────────
        if ($unitId) {
            $unit = Unit::find($unitId);
            if (! $unit) {
                $this->error("Unit ID {$unitId} tidak ditemukan.");
                return null;
            }
            return new Collection([$unit]);
        }

        // ── Scope: shipment ────────────────────────────────────────────────────
        if ($shipmentId) {
            $shipment = Shipment::find($shipmentId);
            if (! $shipment) {
                $this->error("Shipment ID {$shipmentId} tidak ditemukan.");
                return null;
            }
            $units = Unit::where('shipment_id', $shipmentId)->get();
            if ($units->isEmpty()) {
                $this->warn("Shipment {$shipmentId} tidak memiliki unit.");
                return null;
            }
            $this->line("Shipment: {$shipment->code} | {$units->count()} unit");
            return $units;
        }

        // ── Scope: voyage ──────────────────────────────────────────────────────
        if ($voyageId) {
            $voyage = Voyage::find($voyageId);
            if (! $voyage) {
                $this->error("Voyage ID {$voyageId} tidak ditemukan.");
                return null;
            }
            $shipmentIds = Shipment::where('voyage_id', $voyageId)->pluck('id');
            if ($shipmentIds->isEmpty()) {
                $this->warn("Voyage {$voyageId} tidak memiliki shipment.");
                return null;
            }
            $units = Unit::whereIn('shipment_id', $shipmentIds)->get();
            if ($units->isEmpty()) {
                $this->warn("Voyage {$voyageId} tidak memiliki unit.");
                return null;
            }
            $this->line("Voyage: {$voyage->voyage_no} | {$shipmentIds->count()} shipments | {$units->count()} unit");
            return $units;
        }

        // ── Scope: all ─────────────────────────────────────────────────────────
        $units = Unit::all();
        if ($units->isEmpty()) {
            $this->warn('Tidak ada unit di database.');
            return null;
        }
        $this->warn("⚠  Mode --all: akan memproses {$units->count()} unit.");
        return $units;
    }

    private function runDryRun(
        UnitInspectionGenerator $generator,
        Collection $units,
        array $stages
    ): int {
        $totalWillCreate = 0;
        $totalItems      = 0;
        $totalSkip       = 0;

        foreach ($units as $unit) {
            $preview = $generator->preview($unit, $stages);
            foreach ($preview as $stage => $info) {
                if ($info['status'] === 'will_create') {
                    $totalWillCreate++;
                    $totalItems += $info['items'];
                } elseif ($info['status'] === 'already_exists') {
                    $totalSkip++;
                }
            }
        }

        $this->table(
            ['Metric', 'Preview'],
            [
                ['Units',                   $units->count()],
                ['Inspections akan dibuat', $totalWillCreate],
                ['Items akan dibuat',       $totalItems],
                ['Stages sudah ada (skip)', $totalSkip],
            ]
        );

        $this->newLine();
        $this->info('Jalankan tanpa --dry-run untuk eksekusi sesungguhnya.');
        return self::SUCCESS;
    }
}
