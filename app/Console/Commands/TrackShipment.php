<?php

namespace App\Console\Commands;

use App\Enums\TrackStatus;
use App\Models\Shipment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

/**
 * Shipment Tracking Command
 * Update shipment status step by step
 *
 * Usage:
 *   php artisan shipment:track {code} {status}
 *   php artisan shipment:track JSS0426SH1234 pickup
 *   php artisan shipment:track JSS0426SH1234 handover --note="Handover ke depo"
 *   php artisan shipment:track JSS0426SH1234 delivered --note="Diterima oleh ..."
 */
class TrackShipment extends Command
{
    protected $signature = 'shipment:track
                            {code : Shipment code (e.g., JSS0426SH1234)}
                            {status : Tracking status (pickup, handover, stuffing, delivery_to_port, stacking, unit_loading, vessel_depart, vessel_arrival, unloading, delivery_to_customer, delivered)}
                            {--note= : Note for this tracking update}
                            {--location= : Location of the event}
                            {--time= : Custom timestamp (Y-m-d H:i:s), default: now}
                            {--force : Skip MP Check validation}';

    protected $description = 'Update shipment tracking status step by step';

    private array $statusFlow = [
        'pickup' => [
            'label' => 'Penjemputan',
            'default_note' => 'Unit dijemput dari lokasi customer',
            'default_location' => 'Customer Location',
        ],
        'handover' => [
            'label' => 'Handover Depo',
            'default_note' => 'Handover ke depo',
            'default_location' => 'Depo',
        ],
        'stuffing' => [
            'label' => 'Stuffing & Segel',
            'default_note' => 'Stuffing unit ke container',
            'default_location' => 'Depo',
        ],
        'delivery_to_port' => [
            'label' => 'Antar ke Pelabuhan',
            'default_note' => 'Container diantar ke pelabuhan',
            'default_location' => 'Pelabuhan',
        ],
        'stacking' => [
            'label' => 'Stacking (Terminal)',
            'default_note' => 'Container stacking di terminal',
            'default_location' => 'Terminal',
        ],
        'unit_loading' => [
            'label' => 'Dimuat di Kapal',
            'default_note' => 'Unit dimuat ke kapal',
            'default_location' => 'Kapal',
        ],
        'vessel_depart' => [
            'label' => 'Kapal Berangkat',
            'default_note' => 'Kapal berangkat menuju tujuan',
            'default_location' => 'Pelabuhan Asal',
        ],
        'vessel_arrival' => [
            'label' => 'Kapal Tiba',
            'default_note' => 'Kapal tiba di pelabuhan tujuan',
            'default_location' => 'Pelabuhan Tujuan',
        ],
        'unloading' => [
            'label' => 'Pembongkaran',
            'default_note' => 'Pembongkaran unit dari kapal',
            'default_location' => 'Pelabuhan Tujuan',
        ],
        'delivery_to_customer' => [
            'label' => 'Antar ke Customer',
            'default_note' => 'Unit dalam perjalanan ke customer',
            'default_location' => 'Customer Location',
        ],
        'delivered' => [
            'label' => 'Terkirim',
            'default_note' => 'Unit telah diterima oleh customer',
            'default_location' => 'Customer',
        ],
    ];

    public function handle(): int
    {
        $code = $this->argument('code');
        $statusInput = strtolower($this->argument('status'));

        // Find shipment
        $shipment = Shipment::where('code', $code)->first();
        if (! $shipment) {
            $this->error("Shipment {$code} not found!");

            return 1;
        }

        // Validate status
        if (! isset($this->statusFlow[$statusInput])) {
            $this->error("Invalid status: {$statusInput}");
            $this->info('Available statuses: '.implode(', ', array_keys($this->statusFlow)));

            return 1;
        }

        $statusConfig = $this->statusFlow[$statusInput];
        $trackStatus = TrackStatus::tryFrom($statusInput);

        if (! $trackStatus) {
            $this->error("Invalid track status: {$statusInput}");

            return 1;
        }

        // Get FC user (simulate login as FC)
        $fc = \App\Models\User::where('email', 'fc.jkt@jss.local')->first();
        if ($fc) {
            Auth::login($fc);
        }

        // Informational only — appendTrack() guards duplication.
        $existingTrack = $shipment->tracks()->where('status', $trackStatus->value)->first();
        if ($existingTrack && $existingTrack->tracked_at) {
            $this->warn("Status '{$statusConfig['label']}' already recorded at {$existingTrack->tracked_at->format('d M Y H:i')}");
            $this->warn('Cannot overwrite an already-recorded status.');

            return 0;
        }

        // Get inputs
        $note = $this->option('note') ?? $statusConfig['default_note'];
        $location = $this->option('location') ?? $statusConfig['default_location'];
        $time = $this->option('time') ? Carbon::parse($this->option('time')) : now();

        $override = $this->option('force') ? ['reason' => 'Console --force override'] : null;

        // Create or update track — all gates enforced via appendTrack()
        try {
            $track = $shipment->appendTrack(
                $trackStatus,
                $note,
                $location,
                null,
                $override,
            );

            // Output success
            $this->info('');
            $this->info('========================================');
            $this->info('TRACKING UPDATED');
            $this->info('========================================');
            $this->info("Shipment: {$shipment->code}");
            $this->info("Status: {$statusConfig['label']}");
            $this->info("Time: {$time->format('d M Y H:i')}");
            $this->info("Location: {$location}");
            $this->info("Note: {$note}");
            $this->info('');
            $this->info("Shipment Status: {$shipment->status->label()}");

            // Show next steps
            $nextStatus = $this->getNextStatus($statusInput);
            if ($nextStatus) {
                $this->info('');
                $this->info('Next step:');
                $this->info("  php artisan shipment:track {$shipment->code} {$nextStatus}");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return 1;
        }
    }

    private function getNextStatus(string $current): ?string
    {
        $order = [
            'pickup',
            'handover',
            'stuffing',
            'delivery_to_port',
            'stacking',
            'unit_loading',
            'vessel_depart',
            'vessel_arrival',
            'unloading',
            'delivery_to_customer',
            'delivered',
        ];

        $index = array_search($current, $order);
        if ($index !== false && isset($order[$index + 1])) {
            return $order[$index + 1];
        }

        return null;
    }
}
