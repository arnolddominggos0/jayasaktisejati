<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\ShippingSchedule;
use App\Models\VesselCheck;
use App\Models\VesselCheckCase;
use App\Enums\VesselCheckLogStatus;
use App\Enums\VesselCheckStatus;
use Carbon\Carbon;

class SyncVesselChecks extends Command
{
    protected $signature = 'vessel-check:sync';
    protected $description = 'Sinkronisasi vessel check H-3 s.d. H-1 dan auto buka tindak lanjut jika ETD berubah';

    public function handle(): int
    {
        Log::info('Vessel check sync started');

        $today = Carbon::today();

        ShippingSchedule::query()
            ->whereHas('voyage', fn($q) => $q->whereNull('atd_at'))
            ->with(['voyage', 'vesselCheckCase'])
            ->get()
            ->each(function ($schedule) use ($today) {

                $etd = Carbon::parse($schedule->voyage->etd);
                $diff = $today->diffInDays($etd, false);

                if (! in_array($diff, [1, 2, 3], true)) {
                    return;
                }

                $dayCode = 'D-' . $diff;

                $check = VesselCheck::updateOrCreate(
                    [
                        'shipping_schedule_id' => $schedule->id,
                        'check_date' => $today->toDateString(),
                    ],
                    [
                        'day_code'    => $dayCode,
                        'etd_plan'    => $schedule->etd_plan ?? $etd,
                        'etd_current' => $etd,
                        'status'      => VesselCheckLogStatus::ON_SCHEDULE,
                        'source'      => 'SYSTEM',
                    ]
                );

                if (! $check->etd_plan->equalTo($check->etd_current)) {
                    $check->update([
                        'status' => VesselCheckLogStatus::POTENTIAL_DELAY,
                    ]);

                    if (! $schedule->vesselCheckCase) {
                        VesselCheckCase::create([
                            'shipping_schedule_id' => $schedule->id,
                            'case_status' => VesselCheckStatus::ETD_DELAY,
                            'delay_flag'  => true,
                            'opened_at'   => now(),
                        ]);
                    }
                }
            });

        Log::info('Vessel check sync finished');

        return self::SUCCESS;
    }
}
