<?php

namespace App\Actions\Schedule;

use App\Enums\ScheduleState;
use App\Models\ShippingSchedule;

class FinalizeSchedule
{
    public static function run(ShippingSchedule $schedule, string $approvedBy = 'System'): ShippingSchedule
    {
        abort_unless($schedule->canFinalize(), 422, 'Data belum lengkap atau sudah melewati batas revisi.');

        $schedule->forceFill([
            'state'            => ScheduleState::Final,
            'finalized_at'     => now(),
            'approved_by_name' => $approvedBy,
            'final_source'     => $schedule->final_source ?: 'TAM Feedback',
        ])->save();

        return $schedule->fresh();
    }
}
