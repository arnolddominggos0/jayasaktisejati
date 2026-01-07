<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Voyage;

class BuildShippingScheduleNovember extends Command
{
    protected $signature = 'build:shipping-schedule-november';
    protected $description = 'Build shipping schedules November 2025';

    public function handle()
    {
        $period = Carbon::create(2024, 11, 1)->startOfMonth();

        $voyages = Voyage::whereBetween(
            'etd',
            [$period, $period->copy()->endOfMonth()]
        )->get();

        foreach ($voyages as $voyage) {
            $voyage->syncSchedule();
        }

        $this->info('Shipping schedule November generated: ' . $voyages->count());
    }
}
