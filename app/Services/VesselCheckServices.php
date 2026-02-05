<?php

namespace App\Services;

use App\Models\VesselCheck;
use App\Models\VesselCheckCase;
use App\Models\VesselCheckDelay;
use App\Models\VesselCheckAlternative;
use App\Enums\VesselCheckLogStatus;
use App\Enums\VesselCheckStatus;
use App\Exceptions\VesselCheckDomainException;
use Illuminate\Support\Facades\DB;

class VesselCheckService
{
    public function recordDailyCheck(
        int $shippingScheduleId,
        string $dayCode,
        string $etdPlan,
        string $etdCurrent,
        string $source,
        ?string $note = null
    ): VesselCheck {
        return VesselCheck::updateOrCreate(
            [
                'shipping_schedule_id' => $shippingScheduleId,
                'check_date' => today(),
            ],
            [
                'day_code'    => $dayCode,
                'etd_plan'    => $etdPlan,
                'etd_current' => $etdCurrent,
                'status'      => $etdPlan !== $etdCurrent
                    ? VesselCheckLogStatus::POTENTIAL_DELAY
                    : VesselCheckLogStatus::ON_SCHEDULE,
                'source'      => $source,
                'note'        => $note,
            ]
        );
    }

    public function openIssueFromCheck(int $vesselCheckId): VesselCheckCase
    {
        $check = VesselCheck::with('shippingSchedule.vesselCheckCase')
            ->findOrFail($vesselCheckId);

        if ($check->status !== VesselCheckLogStatus::POTENTIAL_DELAY) {
            throw new VesselCheckDomainException('Issue hanya boleh dari potential delay');
        }

        if ($check->shippingSchedule->vesselCheckCase) {
            throw new VesselCheckDomainException('Issue sudah ada');
        }

        return VesselCheckCase::create([
            'shipping_schedule_id' => $check->shipping_schedule_id,
            'case_status'          => VesselCheckStatus::ETD_DELAY,
            'delay_flag'           => true,
            'opened_at'            => now(),
        ]);
    }

    public function analyzeDelay(int $caseId, array $data): void
    {
        $case = VesselCheckCase::findOrFail($caseId);

        if ($case->case_status !== VesselCheckStatus::ETD_DELAY) {
            throw new VesselCheckDomainException('Belum bisa dianalisis');
        }

        DB::transaction(function () use ($case, $data) {
            VesselCheckDelay::create(array_merge(
                $data,
                ['vessel_check_case_id' => $case->id]
            ));

            $case->update([
                'case_status' => VesselCheckStatus::IN_PROGRESS,
            ]);
        });
    }

    public function resolveCase(int $caseId): void
    {
        $case = VesselCheckCase::findOrFail($caseId);

        if (! $case->hasApprovedAlternative()) {
            throw new VesselCheckDomainException('Belum ada solusi');
        }

        $case->update([
            'case_status' => VesselCheckStatus::RESOLVED,
        ]);
    }

    public function closeCase(int $caseId): void
    {
        $case = VesselCheckCase::findOrFail($caseId);

        if ($case->case_status !== VesselCheckStatus::RESOLVED) {
            throw new VesselCheckDomainException('Belum bisa ditutup');
        }

        $case->update([
            'case_status' => VesselCheckStatus::COMPLETED,
            'closed_at'   => now(),
        ]);
    }
}
