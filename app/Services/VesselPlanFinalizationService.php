<?php

namespace App\Services;

use App\Enums\VesselPlanStatus;
use App\Models\VesselPlan;
use App\Models\VesselPlanItem;
use App\Models\Voyage;
use DomainException;
use Illuminate\Support\Facades\DB;

class VesselPlanFinalizationService
{
    public function finalize(VesselPlan $plan, int $userId): int
    {
        if (! $plan->isSent()) {
            throw new DomainException('Harus sent.');
        }

        if (! $plan->syncRoutePorts()) {
            throw new DomainException('POL/POD vessel plan belum terisi. Periksa route_code atau master port.');
        }

        $analysis = $plan->analyze();

        return DB::transaction(function () use ($plan, $userId, $analysis) {
            $plan->snapshots()->create([
                'stage' => VesselPlan::SNAPSHOT_STAGE_FINAL,
                'schedule_payload' => $plan->buildScheduleSnapshot(),
                'kpi_payload' => $plan->buildSopSnapshot($analysis),
                'created_by' => $userId,
            ]);

            $count = 0;

            foreach ($plan->items()->with('voyage')->orderBy('planned_etd')->get() as $item) {
                $this->syncVoyage($plan, $item);
                $count++;
            }

            $plan->update([
                'status' => VesselPlanStatus::Final,
                'final_kpi_total' => round($analysis['sailing_avg'] ?? 0),
                'approved_at' => now(),
                'approved_by' => $userId,
                'finalized_at' => now(),
                'finalized_by' => $userId,
            ]);

            $plan->logReviewAction(
                VesselPlan::REVIEW_ACTION_APPROVED,
                $userId,
                'Final schedule disetujui dan vessel plan difinalisasi.',
                [
                    'status' => VesselPlanStatus::Final->value,
                    'sailing_avg' => $analysis['sailing_avg'] ?? 0,
                    'voyage_count' => $count,
                ]
            );

            return $count;
        });
    }

    protected function syncVoyage(VesselPlan $plan, VesselPlanItem $item): Voyage
    {
        $voyage = $item->voyage()->first();

        $payload = [
            'vessel_plan_id' => $plan->id,
            'vessel_plan_item_id' => $item->id,
            'shipping_line_id' => $item->shipping_line_id,
            'vessel_id' => $item->vessel_id,
            'pol_id' => $plan->pol_id,
            'pod_id' => $plan->pod_id,
            'voyage_no' => $voyage?->voyage_no ?: 'VY-' . $item->planned_etd->format('Ym') . '-' . $item->id,
            'etd' => $item->planned_etd,
            'eta' => $item->planned_eta,
            'period_month' => $plan->period_month,
        ];

        if ($voyage) {
            $voyage->update($payload);
        } else {
            $voyage = Voyage::create($payload);
        }

        if ($item->voyage_id !== $voyage->id) {
            $item->forceFill(['voyage_id' => $voyage->id])->saveQuietly();
        }

        return $voyage;
    }
}
