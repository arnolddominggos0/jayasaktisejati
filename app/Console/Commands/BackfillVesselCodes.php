<?php

namespace App\Console\Commands;

use App\Models\Vessel;
use Illuminate\Console\Command;

class BackfillVesselCodes extends Command
{
    protected $signature = 'app:backfill-vessel-codes';
    protected $description = 'Generate vessel.code for vessels that are missing it';

    public function handle(): int
    {
        $count = 0;
        Vessel::with('shippingLine')->chunk(200, function ($chunk) use (&$count) {
            foreach ($chunk as $v) {
                if (!$v->code) {
                    $v->code = \App\Support\VesselCode::for($v);
                    $v->saveQuietly();
                    $count++;
                }
            }
        });

        $this->info("Updated {$count} vessels.");
        return self::SUCCESS;
    }
}
