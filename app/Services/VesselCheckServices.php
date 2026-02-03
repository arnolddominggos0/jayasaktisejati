<?php
// app/Services/VesselCheckService.php

namespace App\Services;

use App\Models\VesselCheckCase;
use App\Models\VesselCheckDelay;
use App\Models\VesselCheckLog;
use App\Models\VesselCheckAlternative;
use App\Enums\VesselCheckStatus;
use App\Exceptions\VesselCheckDomainException;
use Illuminate\Support\Facades\DB;

class VesselCheckService
{
    public function openCase(int $shippingScheduleId): VesselCheckCase
    {
        return VesselCheckCase::create([
            'shipping_schedule_id' => $shippingScheduleId,
            'case_status' => VesselCheckStatus::ON_SCHEDULE,
            'delay_flag'  => false,
            'opened_at'   => now(),
        ]);
    }

    public function logDailyCheck(
        int $caseId,
        string $dayCode,
        string $etdPlan,
        string $etdCurrent,
        string $source
    ): void {
        $case = VesselCheckCase::findOrFail($caseId);

        VesselCheckLog::create([
            'vessel_check_case_id' => $case->id,
            'check_date' => now(),
            'day_code' => $dayCode,
            'etd_plan' => $etdPlan,
            'etd_current' => $etdCurrent,
            'status' => $etdPlan !== $etdCurrent ? 'delayed' : 'on_schedule',
            'source' => $source,
        ]);

        if ($etdPlan !== $etdCurrent && $case->case_status === VesselCheckStatus::ON_SCHEDULE) {
            $case->update([
                'case_status' => VesselCheckStatus::ETD_DELAY,
                'delay_flag'  => true,
            ]);
        }
    }

    public function analyzeDelay(int $caseId, array $data): void
    {
        $case = VesselCheckCase::findOrFail($caseId);

        if ($case->case_status !== VesselCheckStatus::ETD_DELAY) {
            throw new VesselCheckDomainException('Delay belum bisa dianalisis');
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

    /* =========================
     | APPROVE ALTERNATIVE
     ========================= */
    public function approveAlternative(int $alternativeId): void
    {
        $alt = VesselCheckAlternative::findOrFail($alternativeId);
        $case = $alt->case;

        if ($case->case_status !== VesselCheckStatus::IN_PROGRESS) {
            throw new VesselCheckDomainException('Alternative belum boleh diproses');
        }

        $alt->update([
            'approval_status' => 'APPROVED',
            'approved_at' => now(),
        ]);
    }

    public function resolveCase(int $caseId): void
    {
        $case = VesselCheckCase::findOrFail($caseId);

        if ($case->case_status !== VesselCheckStatus::IN_PROGRESS) {
            throw new VesselCheckDomainException('Case belum bisa di-resolve');
        }

        if (! $case->hasApprovedAlternative()) {
            throw new VesselCheckDomainException('Belum ada solusi final');
        }

        $case->update([
            'case_status' => VesselCheckStatus::RESOLVED,
        ]);
    }

    public function closeCase(int $caseId): void
    {
        $case = VesselCheckCase::findOrFail($caseId);

        if ($case->case_status !== VesselCheckStatus::RESOLVED) {
            throw new VesselCheckDomainException('Case belum bisa ditutup');
        }

        if ($case->hasPendingAlternative() || $case->hasPendingRequest()) {
            throw new VesselCheckDomainException('Masih ada proses berjalan');
        }

        $case->update([
            'case_status' => VesselCheckStatus::COMPLETED,
            'closed_at' => now(),
        ]);
    }
}
