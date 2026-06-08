<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Voyage;
use App\Models\VesselCheck;
use App\Models\VesselCheckCase;
use App\Enums\VesselCheckLogStatus;
use App\Enums\VesselCheckStatus;
use Carbon\Carbon;

/**
 * @deprecated ETD drift detection engine removed as part of Vessel Check simplification.
 *             VesselCheck is now a simple OK/Late checklist filled by operators.
 *             This command is no longer scheduled and should not be called.
 */
class SyncVesselChecks extends Command
{
    protected $signature = 'vessel-check:sync';
    protected $description = '[DEPRECATED] ETD drift detection — no longer in use';

    public function handle(): int
    {
        Log::info('Vessel check sync started');

        $today = Carbon::today();

        Voyage::query()
            ->whereNull('atd_at')
            ->get()
            ->each(function (Voyage $voyage) use ($today) {

                $etd  = Carbon::parse($voyage->etd);
                $diff = (int) $today->diffInDays($etd, false);

                if (! in_array($diff, [1, 2, 3], true)) {
                    return;
                }

                $dayCode = 'D-' . $diff;

                // firstOrCreate preserves etd_plan on subsequent syncs
                $check = VesselCheck::firstOrCreate(
                    [
                        'voyage_id'  => $voyage->id,
                        'check_date' => $today->toDateString(),
                    ],
                    [
                        'day_code'    => $dayCode,
                        'etd_plan'    => $voyage->etd,
                        'etd_current' => $voyage->etd,
                        'status'      => VesselCheckLogStatus::ON_SCHEDULE,
                        'source'      => 'SYSTEM',
                    ]
                );

                // Always refresh etd_current to latest voyage ETD
                $check->update(['etd_current' => $voyage->etd]);

                // Detect ETD shift and escalate
                if ($check->etd_plan && ! $check->etd_plan->equalTo($check->etd_current)) {
                    $check->update(['status' => VesselCheckLogStatus::POTENTIAL_DELAY]);

                    if (! VesselCheckCase::where('voyage_id', $voyage->id)->exists()) {
                        VesselCheckCase::create([
                            'voyage_id'   => $voyage->id,
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
