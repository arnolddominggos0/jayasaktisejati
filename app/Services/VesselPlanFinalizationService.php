<?php

namespace App\Services;

use App\Enums\VesselPlanStatus;
use App\Models\VesselPlan;
use App\Models\VesselPlanItem;
use App\Models\Voyage;
use App\Models\VoyageScheduleHistory;
use App\Supports\RouteCode;
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

        $this->assertAllItemsHaveVoyageNo($plan);

        $analysis = $plan->analyze();

        return DB::transaction(function () use ($plan, $userId, $analysis) {
            $plan->snapshots()->create([
                'stage' => VesselPlan::SNAPSHOT_STAGE_FINAL,
                'schedule_payload' => $plan->buildScheduleSnapshot(),
                'kpi_payload' => $plan->buildSopSnapshot($analysis),
                'created_by' => $userId,
            ]);

            // Ambil draft snapshot payload untuk merekonstruksi Draft Schedule per voyage
            $draftPayload = $this->buildDraftScheduleIndex($plan);

            $count = 0;

            foreach ($plan->items()->with('voyage')->orderBy('planned_etd')->get() as $item) {
                $voyage = $this->syncVoyage($plan, $item);

                // Simpan Schedule History: draft + final
                $this->recordScheduleHistory($voyage, $item, $draftPayload, $userId);

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

    /**
     * Bangun index ETD/ETA dari draft_submitted snapshot, di-key by item_id.
     *
     * @return array<int, array{planned_etd: string|null, planned_eta: string|null, captured_at: string|null}>
     */
    protected function buildDraftScheduleIndex(VesselPlan $plan): array
    {
        $draftSnapshot = $plan->draftSnapshot();

        if (! $draftSnapshot) {
            return [];
        }

        $capturedAt  = $draftSnapshot->created_at?->toIso8601String();
        $payload     = $draftSnapshot->schedule_payload ?? [];

        return collect($payload)
            ->keyBy('item_id')
            ->map(fn ($row) => [
                'planned_etd'  => $row['planned_etd']  ?? null,
                'planned_eta'  => $row['planned_eta']  ?? null,
                'captured_at'  => $capturedAt,
            ])
            ->all();
    }

    /**
     * Upsert voyage_schedule_histories untuk 'draft' dan 'final'.
     *
     * draft  = ETD/ETA dari draft_submitted snapshot, sailing_days dihitung dari sana.
     * final  = ETD/ETA voyage setelah finalisasi, sailing_days dari voyage.etd→eta.
     *
     * updateOrCreate agar re-finalisasi (revisi → final ulang) tidak duplikasi.
     * Jika final sudah ada dan ETD/ETA berubah, sailing_days ikut di-update.
     */
    protected function recordScheduleHistory(
        Voyage         $voyage,
        VesselPlanItem $item,
        array          $draftIndex,
        int            $userId
    ): void {
        $actorName    = optional(\App\Models\User::find($userId))->name ?? 'System';
        $finalizedAt  = now();

        // ── Draft Schedule ──────────────────────────────────────────────────
        $draftRow = $draftIndex[$item->id] ?? null;

        if ($draftRow && ($draftRow['planned_etd'] || $draftRow['planned_eta'])) {
            VoyageScheduleHistory::updateOrCreate(
                [
                    'voyage_id'     => $voyage->id,
                    'schedule_type' => 'draft',
                ],
                [
                    'etd'          => $draftRow['planned_etd'],
                    'eta'          => $draftRow['planned_eta'],
                    'sailing_days' => VoyageScheduleHistory::calcSailingDays(
                        $draftRow['planned_etd'],
                        $draftRow['planned_eta']
                    ),
                    'notes'        => 'Draft schedule — jadwal awal sebelum persetujuan TAM',
                    'captured_at'  => $draftRow['captured_at'] ?? $finalizedAt,
                    'captured_by'  => $actorName,
                ]
            );
        }

        // ── Final Schedule ──────────────────────────────────────────────────
        VoyageScheduleHistory::updateOrCreate(
            [
                'voyage_id'     => $voyage->id,
                'schedule_type' => 'final',
            ],
            [
                'etd'          => $voyage->etd,
                'eta'          => $voyage->eta,
                'sailing_days' => VoyageScheduleHistory::calcSailingDays(
                    $voyage->etd,
                    $voyage->eta
                ),
                'notes'        => 'Final schedule — disetujui dan difinalisasi oleh TAM',
                'captured_at'  => $finalizedAt,
                'captured_by'  => $actorName,
            ]
        );
    }

    /**
     * Validate that every item in the plan has a real voyage_no before finalization.
     * voyage_no must come from TAM schedule, CSV import, or user input — never generated.
     */
    protected function assertAllItemsHaveVoyageNo(VesselPlan $plan): void
    {
        $missing = $plan->items()
            ->with('vessel')
            ->whereNull('voyage_no')
            ->orWhere('voyage_no', '')
            ->orderBy('planned_etd')
            ->get();

        if ($missing->isEmpty()) {
            return;
        }

        $details = $missing->map(fn (VesselPlanItem $it) =>
            sprintf(
                '- %s (ETD %s, item_id %d)',
                $it->vessel?->name ?? 'Unknown vessel',
                $it->planned_etd?->format('d M Y') ?? '?',
                $it->id,
            )
        )->implode("\n");

        throw new DomainException(
            "Finalisasi gagal: {$missing->count()} item belum memiliki voyage_no.\n" .
            "Isi voyage_no dari schedule TAM sebelum melanjutkan:\n{$details}"
        );
    }

    protected function syncVoyage(VesselPlan $plan, VesselPlanItem $item): Voyage
    {
        $voyage = $item->voyage()->first();

        $payload = [
            'vessel_plan_id'      => $plan->id,
            'vessel_plan_item_id' => $item->id,
            'shipping_line_id'    => $item->shipping_line_id,
            'vessel_id'           => $item->vessel_id,
            'pol_id'              => $plan->pol_id,
            'pod_id'              => $plan->pod_id,
            'route_code'          => RouteCode::voyage((string) $plan->route_code) ?? $plan->route_code,
            'voyage_no'           => $item->voyage_no,
            'etd'                 => $item->planned_etd,
            'etb'                 => $item->planned_etb,
            'eta'                 => $item->planned_eta,
            'cargo_plan'          => $item->cargo_plan,
            'period_month'        => $plan->period_month,
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
