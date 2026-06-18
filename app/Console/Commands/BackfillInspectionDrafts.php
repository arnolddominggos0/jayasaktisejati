<?php

namespace App\Console\Commands;

use App\Models\Shipment;
use App\Models\UnitInspection;
use App\Services\InspectionDraftAutoCreate;
use Illuminate\Console\Command;

/**
 * Backfills missing UnitInspection draft records for active shipments.
 *
 * Scope: shipments with status 'pending' or 'transit' that already have
 * at least one shipment_track row. Shipments without tracks are excluded
 * because their drafts will be created by InspectionDraftAutoCreate when
 * sendToFc() runs.
 *
 * Idempotent: uses firstOrCreate — safe to run multiple times.
 *
 * Usage:
 *   php artisan inspect:backfill-drafts             # apply
 *   php artisan inspect:backfill-drafts --dry-run   # preview only
 *   php artisan inspect:backfill-drafts --shipment=218  # single shipment
 */
class BackfillInspectionDrafts extends Command
{
    protected $signature = 'inspect:backfill-drafts
                            {--dry-run : Preview without creating any records}
                            {--shipment= : Limit to a single shipment ID}';

    protected $description = 'Backfill missing inspection draft records for pending/transit shipments';

    public function handle(): int
    {
        $dryRun     = (bool) $this->option('dry-run');
        $shipmentId = $this->option('shipment');

        if ($dryRun) {
            $this->warn('DRY RUN — tidak ada record yang akan dibuat.');
            $this->newLine();
        }

        $query = Shipment::query()
            ->whereIn('status', ['pending', 'transit'])
            ->whereHas('tracks');       // only shipments with at least one track

        if ($shipmentId) {
            $query->where('id', (int) $shipmentId);
        }

        $shipments = $query->get(['id', 'code', 'status']);

        if ($shipments->isEmpty()) {
            $this->info('Tidak ada shipment dalam scope — selesai.');
            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Memproses %d shipment (status: pending/transit, memiliki tracks).',
            $shipments->count()
        ));
        $this->newLine();

        $totalCreated = 0;
        $totalSkipped = 0;
        $rows         = [];

        foreach ($shipments as $shipment) {
            // Use relation method directly — $shipment->units is shadowed by
            // the legacy JSON cast on the Shipment model.
            $units = $shipment->units()->get(['id', 'shipment_id']);

            if ($units->isEmpty()) {
                $statusStr = $shipment->status instanceof \BackedEnum ? $shipment->status->value : (string) $shipment->status;
            $rows[] = [$shipment->id, $shipment->code, $statusStr, 0, 0, 0, 'no units'];
                continue;
            }

            $shipmentCreated = 0;
            $shipmentSkipped = 0;

            if (! $dryRun) {
                foreach (UnitInspection::STAGES as $stage) {
                    $result = InspectionDraftAutoCreate::ensureForShipmentAndStage($shipment, $stage);
                    $shipmentCreated += $result['created'];
                    $shipmentSkipped += $result['skipped'];
                }
            } else {
                // Dry-run: count what would be created without touching the DB
                foreach ($units as $unit) {
                    foreach (UnitInspection::STAGES as $stage) {
                        $exists = \App\Models\UnitInspection::where('unit_id', $unit->id)
                            ->where('stage', $stage)
                            ->exists();
                        if ($exists) {
                            $shipmentSkipped++;
                        } else {
                            $shipmentCreated++;
                        }
                    }
                }
            }

            $totalCreated += $shipmentCreated;
            $totalSkipped += $shipmentSkipped;

            $statusStr = $shipment->status instanceof \BackedEnum ? $shipment->status->value : (string) $shipment->status;
            $rows[] = [
                $shipment->id,
                $shipment->code,
                $statusStr,
                $units->count(),
                $shipmentCreated,
                $shipmentSkipped,
                $shipmentCreated === 0 ? 'ok' : 'backfilled',
            ];
        }

        $this->table(
            ['Shipment ID', 'Code', 'Status', 'Units', 'Dibuat', 'Dilewati', 'Keterangan'],
            $rows
        );
        $this->newLine();

        if ($dryRun) {
            $this->warn(sprintf(
                'DRY RUN selesai. %d draft AKAN dibuat, %d sudah ada.',
                $totalCreated,
                $totalSkipped
            ));
        } else {
            $this->info(sprintf(
                'Selesai. %d draft dibuat, %d sudah ada (dilewati).',
                $totalCreated,
                $totalSkipped
            ));
        }

        return self::SUCCESS;
    }
}
