<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ShippingSchedule;
use App\Models\VesselCheck;
use App\Enums\VesselCheckLogStatus;
use Carbon\Carbon;

class GenerateDailyVesselChecks extends Command
{
    protected $signature = 'vessel-check:generate-daily';

    protected $description = 'Generate daily vessel checks for H-3 to H-1';

    public function handle(): int
    {
        $today = today();

        // Ambil schedule yang ETD-nya H-3 s/d H-1
        $schedules = ShippingSchedule::query()
            ->whereHas('voyage', function ($q) use ($today) {
                $q->whereBetween(
                    'etd',
                    [
                        $today->copy()->addDays(1)->startOfDay(),
                        $today->copy()->addDays(3)->endOfDay(),
                    ]
                )
                    ->whereNull('atd_at');
            })
            ->with('voyage')
            ->get();

        foreach ($schedules as $schedule) {
            $etd = Carbon::parse($schedule->voyage->etd);
            $diff = $today->diffInDays($etd, false);

            if (! in_array($diff, [1, 2, 3])) {
                continue;
            }

            $dayCode = "H-{$diff}";

            VesselCheck::updateOrCreate(
                [
                    'shipping_schedule_id' => $schedule->id,
                    'check_date' => $today,
                ],
                [
                    'day_code' => $dayCode,
                    'etd_plan' => $schedule->voyage->etd,
                    'etd_current' => $schedule->voyage->etd,
                    'status' => VesselCheckLogStatus::ON_SCHEDULE,
                    'source' => 'SYSTEM',
                    'note' => 'Auto-generated daily vessel check',
                ]
            );
        }

        $this->info('Daily vessel checks generated.');

        return self::SUCCESS;
    }
}
