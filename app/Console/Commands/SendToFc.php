<?php

namespace App\Console\Commands;

use App\Models\Shipment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

/**
 * Send Shipment to Field Coordinator
 * Change status from DRAFT to PENDING
 *
 * Usage:
 *   php artisan shipment:send-to-fc {code}
 *   php artisan shipment:send-to-fc JSS0426SH0001
 */
class SendToFc extends Command
{
    protected $signature = 'shipment:send-to-fc
                            {code : Shipment code (e.g., JSS0426SH0001)}';

    protected $description = 'Send shipment to Field Coordinator';

    public function handle(): int
    {
        $code = $this->argument('code');

        $shipment = Shipment::where('code', $code)->first();
        if (! $shipment) {
            $this->error("Shipment {$code} not found!");

            return 1;
        }

        if ($shipment->status->value !== 'draft') {
            $this->error("Shipment status is not DRAFT (current: {$shipment->status->label()})");

            return 1;
        }

        // Login as FC
        $fc = \App\Models\User::where('email', 'fc.jkt@jss.local')->first();
        if ($fc) {
            Auth::login($fc);
        }

        try {
            $shipment->sendToFc();
            $shipment->refresh();

            $this->info('');
            $this->info('========================================');
            $this->info('SHIPMENT SENT TO FC');
            $this->info('========================================');
            $this->info("Shipment: {$shipment->code}");
            $this->info("Status: {$shipment->status->label()}");
            $this->info("Depot: {$shipment->assignedDepot->name}");
            $this->info('');
            $this->info('Next step:');
            $this->info("  php artisan shipment:track {$shipment->code} pickup");

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return 1;
        }
    }
}
