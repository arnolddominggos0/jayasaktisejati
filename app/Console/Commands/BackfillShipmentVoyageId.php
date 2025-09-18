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
                foreach ($rows as $s) {
                    $q = Voyage::query();

                    if ($s->voyage) {
                        $q->where('voyage_no', $s->voyage);
                    }

                    if ($s->vessel_name) {
                        $q->whereHas('vessel', fn($qq) =>
                            $qq->where('name', 'ilike', $s->vessel_name));
                    }

                    if ($s->pol) {
                        $q->whereHas('portFrom', fn($qq) =>
                            $qq->where('code', 'ilike', $s->pol)->orWhere('name', 'ilike', $s->pol));
                    }
                    if ($s->pod) {
                        $q->whereHas('portTo', fn($qq) =>
                            $qq->where('code', 'ilike', $s->pod)->orWhere('name', 'ilike', $s->pod));
                    }

                    if ($s->etd) {
                        $etd = Carbon::parse($s->etd)->startOfDay();
                        $q->whereBetween('etd', [$etd, (clone $etd)->endOfDay()]);
                    }

                    $voy = $q->orderByDesc('etd')->first();
                    if ($voy) { 
                        $s->voyage_id = $voy->id;
                        $s->saveQuietly();
                        $count++;
                    }
                }
            });

        $this->info("Backfilled {$count} shipments.");
        return self::SUCCESS;
    }
}
