<?php

namespace App\Services;

use App\Enums\VesselPlanStatus;
use App\Models\VesselPlan;
use App\Models\VesselPlanSnapshot;
use DomainException;
use Illuminate\Support\Facades\DB;

class VesselPlanSubmissionService
{
    public function submit(VesselPlan $plan, int $userId): VesselPlanSnapshot
    {
        if (! $plan->isDraft()) {
            throw new DomainException('Harus draft.');
        }

        if (! $plan->customer_id) {
            throw new DomainException('Customer TAM belum diatur pada vessel plan.');
        }

        if (! $plan->whatsapp_phone) {
            throw new DomainException('Nomor WhatsApp customer TAM belum tersedia.');
        }

        $analysis = $plan->analyze();

        return DB::transaction(function () use ($plan, $userId, $analysis) {
            $snapshot = $plan->snapshots()->create([
                'stage' => VesselPlan::SNAPSHOT_STAGE_DRAFT,
                'schedule_payload' => $plan->buildScheduleSnapshot(),
                'kpi_payload' => $plan->buildSopSnapshot($analysis),
                'created_by' => $userId,
            ]);

            $plan->update([
                'status' => VesselPlanStatus::Sent,
                'sent_at' => now(),
                'sent_by' => $userId,
                'draft_kpi_total' => round($analysis['sailing_avg'] ?? 0),
            ]);

            $plan->logReviewAction(
                VesselPlan::REVIEW_ACTION_DRAFT_SUBMITTED,
                $userId,
                'Draft vessel plan dikirim ke customer untuk review.',
                [
                    'status' => VesselPlanStatus::Sent->value,
                    'sailing_avg' => $analysis['sailing_avg'] ?? 0,
                    'snapshot_id' => $snapshot->id,
                ]
            );

            return $snapshot;
        });
    }
}
