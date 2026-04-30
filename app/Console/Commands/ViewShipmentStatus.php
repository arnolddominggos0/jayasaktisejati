<?php

namespace App\Console\Commands;

use App\Models\Shipment;
use App\Models\ShipmentTrack;
use App\Models\Unit;
use Illuminate\Console\Command;

/**
 * View Shipment Status
 * Display complete shipment information and tracking history
 *
 * Usage:
 *   php artisan shipment:status {code}
 *   php artisan shipment:status JSS0426SH0001
 */
class ViewShipmentStatus extends Command
{
    protected $signature = 'shipment:status
                            {code : Shipment code (e.g., JSS0426SH0001)}';

    protected $description = 'View shipment status and tracking history';

    public function handle(): int
    {
        $code = $this->argument('code');

        $shipment = Shipment::with(['customer', 'originCity', 'destinationCity', 'assignedDepot', 'voyage.vessel'])
            ->where('code', $code)
            ->first();

        if (! $shipment) {
            $this->error("Shipment {$code} not found!");

            return 1;
        }

        $this->info('');
        $this->info('========================================');
        $this->info('SHIPMENT STATUS');
        $this->info('========================================');

        // Shipment Info
        $this->info('');
        $this->info('SHIPMENT:');
        $this->info("  Code: {$shipment->code}");
        $this->info("  Customer: {$shipment->customer->name}");
        $this->info("  PIC: {$shipment->pic_name}");
        $this->info("  Route: {$shipment->route_summary}");
        $this->info("  Service: {$shipment->service_type->label()}");
        $this->info("  Priority: {$shipment->priority}");
        $this->info("  Status: {$shipment->status->label()}");
        $this->info("  ETD: {$shipment->etd->format('d M Y')}");
        $this->info("  ETA: {$shipment->eta->format('d M Y')}");

        if ($shipment->delivered_at) {
            $this->info("  Delivered At: {$shipment->delivered_at->format('d M Y H:i')}");
            $leadTime = $shipment->lead_time_days;
            if ($leadTime !== null) {
                $this->info("  Lead Time: {$leadTime} days");
            }
        }

        // Units
        $this->info('');
        $this->info('UNITS:');
        $units = Unit::where('shipment_id', $shipment->id)->get();
        foreach ($units as $i => $unit) {
            $this->info('  '.($i + 1).". {$unit->model_no}");
            $this->info("     Chassis: {$unit->chassis_no}");
            $this->info("     Color: {$unit->color}");
            if ($unit->notes) {
                $this->info("     Remarks: {$unit->notes}");
            }
        }

        // Tracking History
        $this->info('');
        $this->info('TRACKING HISTORY:');
        $tracks = ShipmentTrack::where('shipment_id', $shipment->id)
            ->whereNotNull('tracked_at')
            ->orderBy('tracked_at')
            ->get();

        if ($tracks->isEmpty()) {
            $this->warn('  No tracking records yet.');
            $this->info('');
            $this->info('To start tracking:');
            $this->info("  php artisan shipment:send-to-fc {$shipment->code}");
        } else {
            foreach ($tracks as $track) {
                $this->info("  [{$track->tracked_at->format('d M H:i')}] {$track->status->label()}");
                $this->info("    └─ {$track->note}");
                if ($track->location) {
                    $this->info("    └─ Location: {$track->location}");
                }
            }
        }

        // Next Steps
        $this->info('');
        $this->info('========================================');

        if ($shipment->status->value === 'draft') {
            $this->info('NEXT STEP:');
            $this->info("  php artisan shipment:send-to-fc {$shipment->code}");
        } elseif ($shipment->status->value !== 'delivered' && $shipment->status->value !== 'cancelled') {
            $nextStatus = $this->getNextStatus($tracks->last()?->status->value ?? '');
            if ($nextStatus) {
                $this->info('NEXT STEP:');
                $this->info("  php artisan shipment:track {$shipment->code} {$nextStatus}");
            }
        } else {
            $this->info('SHIPMENT COMPLETED');
        }

        $this->info('========================================');

        return 0;
    }

    private function getNextStatus(?string $current): ?string
    {
        $order = [
            '' => 'pickup',
            'pickup' => 'handover',
            'handover' => 'stuffing',
            'stuffing' => 'delivery_to_port',
            'delivery_to_port' => 'stacking',
            'stacking' => 'unit_loading',
            'unit_loading' => 'vessel_depart',
            'vessel_depart' => 'vessel_arrival',
            'vessel_arrival' => 'unloading',
            'unloading' => 'delivery_to_customer',
            'delivery_to_customer' => 'delivered',
        ];

        return $order[$current] ?? null;
    }
}
