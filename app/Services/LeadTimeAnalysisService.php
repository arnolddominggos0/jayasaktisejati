<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\Voyage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LeadTimeAnalysisService
{
    public function __construct(
        private ShipmentKpiEvaluator $evaluator
    ) {}

    /**
     * Return base query for TAM shipments: same filters as AdminDashboard::tamBaseQuery()
     */
    private function tamQuery(Carbon $start, Carbon $end): \Illuminate\Database\Eloquent\Builder
    {
        $cfg = config('jss_kpi.manado', []);
        $customerIds = array_map('intval', $cfg['customer_ids'] ?? []);

        return Shipment::query()
            ->when(! empty($customerIds), fn ($q) => $q->whereIn('customer_id', $customerIds))
            ->whereNotNull('delivered_at')
            ->whereBetween('delivered_at', [$start, $end]);
    }

    /**
     * Voyage summary list grouped by voyage_id.
     * Returns array of voyage rows with aggregated KPI.
     */
    public function getVoyageSummaries(Carbon $start, Carbon $end, ?string $voyageSearch = null): Collection
    {
        $shipments = $this->tamQuery($start, $end)
            ->whereNotNull('voyage_id')
            ->with([
                'voyage.vessel',
                'voyage',
                'tracks:id,shipment_id,status,tracked_at',
                'units:id,shipment_id,chassis_no,engine_no',
            ])
            ->get();

        if ($voyageSearch) {
            $search = mb_strtolower($voyageSearch);
            $shipments = $shipments->filter(function ($s) use ($search) {
                $label = mb_strtolower(
                    ($s->voyage?->vessel?->name ?? '') . ' ' . ($s->voyage?->voyage_no ?? '')
                );
                return str_contains($label, $search);
            });
        }

        return $shipments
            ->filter(fn ($s) => $s->voyage_id)
            ->groupBy('voyage_id')
            ->map(function (Collection $group) {
                $firstShipment = $group->first();
                $voyage = $firstShipment->voyage;

                $sumDw = $sumSa = $sumDo = $sumTt = 0.0;
                $nKpi = $ok = $ng = 0;

                foreach ($group as $shipment) {
                    $ev = $this->evaluator->evaluateManadoKpi($shipment);
                    if (! ($ev['applies'] ?? false)) {
                        continue;
                    }
                    $s = $ev['summary'] ?? [];
                    $dw = $s['dwelling']['actual'] ?? null;
                    $sa = $s['sailing']['actual'] ?? null;
                    $do = $s['dooring']['actual'] ?? null;
                    $tt = $s['total']['actual'] ?? null;

                    if ($dw !== null && $sa !== null && $do !== null && $tt !== null) {
                        $sumDw += $dw;
                        $sumSa += $sa;
                        $sumDo += $do;
                        $sumTt += $tt;
                        $nKpi++;
                    }

                    $badge = $ev['badge'] ?? null;
                    if (in_array($badge, ['On Time', 'Tepat Waktu'], true)) {
                        $ok++;
                    } elseif (in_array($badge, ['Late', 'Terlambat'], true)) {
                        $ng++;
                    }
                }

                $qtyUnit = $group->sum(fn ($s) => max(1, $s->units->count() ?: 1));

                return [
                    'voyage_id'    => $voyage?->id,
                    'voyage_label' => trim(($voyage?->vessel?->name ?? '-') . ' ' . ($voyage?->voyage_no ?? '')),
                    'vessel_name'  => $voyage?->vessel?->name ?? '-',
                    'voyage_no'    => $voyage?->voyage_no ?? '-',
                    'period_label' => $voyage?->period_month
                        ? Carbon::parse($voyage->period_month)->translatedFormat('F Y')
                        : $firstShipment->delivered_at->translatedFormat('F Y'),
                    'qty_unit'     => $qtyUnit,
                    'avg_dwelling' => $nKpi > 0 ? round($sumDw / $nKpi, 2) : null,
                    'avg_sailing'  => $nKpi > 0 ? round($sumSa / $nKpi, 2) : null,
                    'avg_dooring'  => $nKpi > 0 ? round($sumDo / $nKpi, 2) : null,
                    'avg_lt'       => $nKpi > 0 ? round($sumTt / $nKpi, 2) : null,
                    'ok_count'     => $ok,
                    'ng_count'     => $ng,
                    'etd'          => $voyage?->etd,
                    'ata'          => $voyage?->ata_at,
                ];
            })
            ->sortByDesc('etd')
            ->values();
    }

    /**
     * Per-unit rows for a voyage. Each row = one Unit record, KPI from parent Shipment.
     */
    public function getVoyageUnits(int $voyageId, ?string $search = null, ?string $statusFilter = null): Collection
    {
        $shipments = Shipment::query()
            ->where('voyage_id', $voyageId)
            ->whereNotNull('delivered_at')
            ->with([
                'tracks:id,shipment_id,status,tracked_at',
                'units:id,shipment_id,chassis_no,engine_no,model_no,color',
            ])
            ->get();

        $rows = collect();

        foreach ($shipments as $shipment) {
            $ev = $this->evaluator->evaluateManadoKpi($shipment);
            $applies = $ev['applies'] ?? false;
            $s = $applies ? ($ev['summary'] ?? []) : [];

            $dw   = $s['dwelling']['actual'] ?? null;
            $sa   = $s['sailing']['actual'] ?? null;
            $do   = $s['dooring']['actual'] ?? null;
            $tt   = $s['total']['actual'] ?? null;
            $dwSt = $applies ? ($s['dwelling']['status'] ?? 'PENDING') : 'PENDING';
            $saSt = $applies ? ($s['sailing']['status'] ?? 'PENDING') : 'PENDING';
            $doSt = $applies ? ($s['dooring']['status'] ?? 'PENDING') : 'PENDING';
            $ttSt = $applies ? ($s['total']['status'] ?? 'PENDING') : 'PENDING';

            $units = $shipment->units;

            if ($units->isEmpty()) {
                $rows->push([
                    'shipment_id'   => $shipment->id,
                    'unit_id'       => null,
                    'chassis_no'    => $shipment->code ?? '-',
                    'engine_no'     => '-',
                    'model'         => '-',
                    'color'         => '-',
                    'dwelling'      => $dw,
                    'dwelling_st'   => $dwSt,
                    'sailing'       => $sa,
                    'sailing_st'    => $saSt,
                    'dooring'       => $do,
                    'dooring_st'    => $doSt,
                    'lt_total'      => $tt,
                    'lt_status'     => $ttSt,
                ]);
            } else {
                foreach ($units as $unit) {
                    $rows->push([
                        'shipment_id'   => $shipment->id,
                        'unit_id'       => $unit->id,
                        'chassis_no'    => $unit->chassis_no ?? $shipment->code ?? '-',
                        'engine_no'     => $unit->engine_no ?? '-',
                        'model'         => $unit->model_no ?? '-',
                        'color'         => $unit->color ?? '-',
                        'dwelling'      => $dw,
                        'dwelling_st'   => $dwSt,
                        'sailing'       => $sa,
                        'sailing_st'    => $saSt,
                        'dooring'       => $do,
                        'dooring_st'    => $doSt,
                        'lt_total'      => $tt,
                        'lt_status'     => $ttSt,
                    ]);
                }
            }
        }

        if ($search) {
            $q = mb_strtolower($search);
            $rows = $rows->filter(fn ($r) =>
                str_contains(mb_strtolower($r['chassis_no']), $q) ||
                str_contains(mb_strtolower($r['engine_no']), $q)
            );
        }

        if ($statusFilter && in_array($statusFilter, ['OK', 'NG'], true)) {
            $target = $statusFilter === 'OK' ? 'OK' : 'LATE';
            $rows = $rows->filter(fn ($r) => $r['lt_status'] === $target);
        }

        return $rows->values();
    }

    /**
     * Full detail for a single shipment (for unit detail view).
     */
    public function getShipmentDetail(int $shipmentId): ?array
    {
        $shipment = Shipment::with([
            'voyage.vessel',
            'voyage',
            'tracks:id,shipment_id,status,tracked_at,note',
            'units',
            'customer:id,name',
        ])->find($shipmentId);

        if (! $shipment) {
            return null;
        }

        $ev = $this->evaluator->evaluateManadoKpi($shipment);
        $ms = $this->evaluator->getMilestoneTimes($shipment);
        $t  = $this->evaluator->getManadoThresholds();

        $applies = $ev['applies'] ?? false;
        $s = $applies ? ($ev['summary'] ?? []) : [];

        $timeline = $this->buildTimeline($shipment, $ms);

        return [
            'shipment'   => $shipment,
            'voyage'     => $shipment->voyage,
            'applies'    => $applies,
            'summary'    => $s,
            'thresholds' => $t,
            'badge'      => $ev['badge'] ?? null,
            'milestones' => $ms,
            'timeline'   => $timeline,
        ];
    }

    private function buildTimeline(Shipment $shipment, array $ms): array
    {
        $tracks = $shipment->tracks->sortBy('tracked_at');

        $steps = [];

        if ($ms['pickup']) {
            $steps[] = ['label' => 'Gate In / Pickup', 'at' => Carbon::parse($ms['pickup'])];
        }
        if ($ms['onboard']) {
            $steps[] = ['label' => 'Loaded / Vessel Depart', 'at' => Carbon::parse($ms['onboard'])];
        }
        if ($ms['arrived']) {
            $steps[] = ['label' => 'Vessel Arrival', 'at' => Carbon::parse($ms['arrived'])];
        }
        if ($ms['deliv']) {
            $steps[] = ['label' => 'Delivered', 'at' => Carbon::parse($ms['deliv'])];
        }

        $result = [];
        foreach ($steps as $i => $step) {
            $days = null;
            if ($i > 0 && $steps[$i - 1]['at']) {
                $days = $steps[$i - 1]['at']->startOfDay()->diffInDays($step['at']->copy()->startOfDay());
            }
            $result[] = [
                'label' => $step['label'],
                'at'    => $step['at']->format('d M Y'),
                'days'  => $days,
            ];
        }

        return $result;
    }

    /**
     * Voyage info for display in detail header.
     */
    public function getVoyageInfo(int $voyageId): ?array
    {
        $voyage = Voyage::with('vessel')->find($voyageId);
        if (! $voyage) {
            return null;
        }

        return [
            'id'     => $voyage->id,
            'label'  => trim(($voyage->vessel?->name ?? '-') . ' ' . ($voyage->voyage_no ?? '')),
            'etd'    => $voyage->etd?->format('d M Y'),
            'ata'    => $voyage->ata_at?->format('d M Y'),
            'period' => $voyage->period_month
                ? Carbon::parse($voyage->period_month)->translatedFormat('F Y')
                : null,
        ];
    }
}
