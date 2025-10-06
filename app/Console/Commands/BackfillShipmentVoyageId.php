<?php

namespace App\Console\Commands;

use App\Models\Shipment;
use App\Models\Voyage;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackfillShipmentVoyageId extends Command
{
    protected $signature = 'data:backfill-shipment-voyage';
    protected $description = 'Isi kolom voyage_id di shipments dari snapshot lama';

    public function handle(): int
    {
        $count = 0;

        Shipment::whereNull('voyage_id')
            ->chunkById(500, function ($rows) use (&$count) {
                foreach ($rows as $state) {
                    $q = Voyage::query();

                    if ($state->voyage) {
                        $q->where('voyage_no', $state->voyage);
                    }

                    if ($state->vessel_name) {
                        $q->whereHas('vessel', fn($qq) =>
                            $qq->where('name', 'ilike', $state->vessel_name));
                    }

                    if ($state->pol) {
                        $q->whereHas('portFrom', fn($qq) =>
                            $qq->where('code', 'ilike', $state->pol)->orWhere('name', 'ilike', $state->pol));
                    }
                    if ($state->pod) {
                        $q->whereHas('portTo', fn($qq) =>
                            $qq->where('code', 'ilike', $state->pod)->orWhere('name', 'ilike', $state->pod));
                    }

                    if ($state->etd) {
                        $etd = Carbon::parse($state->etd)->startOfDay();
                        $q->whereBetween('etd', [$etd, (clone $etd)->endOfDay()]);
                    }

                    $voy = $q->orderByDesc('etd')->first();
                    if ($voy) { 
                        $state->voyage_id = $voy->id;
                        $state->saveQuietly();
                        $count++;
                    }
                }
            });

        $this->info("Backfilled {$count} shipments.");
        return self::SUCCESS;
    }
}
