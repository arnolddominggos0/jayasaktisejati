<?php

namespace App\Console;

use App\Console\Commands\BackfillVesselCodes;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\GenerateDailyVesselChecks;
use Illuminate\Console\Scheduling\Schedule;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        GenerateDailyVesselChecks::class,
        BackfillVesselCodes::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('vessel-check:generate-daily')->dailyAt('01:00');
        $schedule->command('app:backfill-vessel-codes')->weekly();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
