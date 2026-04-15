<?php

namespace App\Console\Commands;

use App\Models\Shipment;
use App\Models\ShipmentTrack;
use App\Models\Unit;
use Illuminate\Console\Command;

class VerifySppb extends Command
{
    protected $signature = 'app:verify-sppb {code=JSS0426SH7023}';

    protected $description = 'Verify SPPB Urgent Ternate data';

    public function handle(): void
    {
        $code = $this->argument('code');
        $shipment = Shipment::where('code', $code)->first();

        if (! $shipment) {
            $this->error("Shipment {$code} not found!");

            return;
        }

        $this->info('========================================');
        $this->info('VERIFIKASI DATA REAL SPPB URGENT');
        $this->info('========================================');
        $this->info('');

        $this->info('SHIPMENT:');
        $this->info('  Code: '.$shipment->code);
        $this->info('  Customer: '.$shipment->customer->name);
        $this->info('  PIC: '.$shipment->pic_name);
        $this->info('  Route: '.$shipment->route_summary);
        $this->info('  Service: '.($shipment->delivery_scope?->value ?? 'N/A'));
        $this->info('  Priority: '.$shipment->priority);
        $this->info('  Status: '.$shipment->status->label());
        $this->info('  ETD: '.$shipment->etd);
        $this->info('  ETA: '.$shipment->eta);
        $this->info('  Delivered At: '.$shipment->delivered_at);
        $this->info('');

        $this->info('UNITS:');
        $units = Unit::where('shipment_id', $shipment->id)->get();
        foreach ($units as $i => $unit) {
            $this->info('  '.($i + 1).'. '.$unit->model_no);
            $this->info('     Reg: '.$unit->reg_no);
            $this->info('     Chassis: '.$unit->chassis_no);
            $this->info('     Engine: '.$unit->engine_no);
            $this->info('     Color: '.$unit->color);
            $this->info('     DO: '.$unit->do_number);
            if ($unit->notes) {
                $this->info('     Remarks: '.$unit->notes);
            }
            $this->info('');
        }

        $this->info('TRACKING HISTORY:');
        $tracks = ShipmentTrack::where('shipment_id', $shipment->id)
            ->whereNotNull('tracked_at')
            ->orderBy('tracked_at')
            ->get();
        foreach ($tracks as $track) {
            $this->info('  ['.$track->tracked_at->format('d M H:i').'] '.$track->status->label().' - '.$track->note);
        }

        $this->info('');
        $this->info('========================================');
        $this->info('Lead Time: '.$shipment->lead_time_days.' days');
        $this->info('Total Tracks: '.$tracks->count());
        $this->info('========================================');
    }
}
