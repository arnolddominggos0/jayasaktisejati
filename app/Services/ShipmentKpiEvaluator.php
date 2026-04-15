<?php

namespace App\Services;

use App\Enums\TrackStatus;
use App\Models\Shipment;
use Illuminate\Support\Carbon;

class ShipmentKpiEvaluator
{
    public function isManadoKpiTarget(Shipment $shipment): bool
    {
        $cfg = config('jss_kpi.manado', []);
        $customerIds = array_map('intval', $cfg['customer_ids'] ?? []);

        if (empty($customerIds)) {
            return false;
        }

        return in_array((int) ($shipment->customer_id ?? 0), $customerIds, true);
    }

    public function getManadoThresholds(): array
    {
        return config('jss_kpi.manado', [
            'dwelling_days' => 5,
            'sailing_days' => 10,
            'dooring_days' => 2,
            'total_days' => ['normal' => 19, 'urgent' => 17],
        ])['thresholds'] ?? [
            'dwelling_days' => 5,
            'sailing_days' => 10,
            'dooring_days' => 2,
            'total_days' => ['normal' => 19, 'urgent' => 17],
        ];
    }

    public function getMilestoneTimes(Shipment $shipment): array
    {
        $tracks = ($shipment->relationLoaded('tracks') ? $shipment->tracks : $shipment->tracks()->get())
            ->filter(fn ($t) => ! empty($t->tracked_at))
            ->sortBy('tracked_at')
            ->values();

        $toVal = fn ($s) => $s instanceof TrackStatus ? $s->value : (string) $s;

        $first = fn (array $set) => optional(
            $tracks->first(fn ($t) => in_array($toVal($t->status), $set, true))
        )->tracked_at;

        $last = fn (string $s) => optional(
            $tracks->last(fn ($t) => $toVal($t->status) === $s)
        )->tracked_at;

        return [
            'pickup' => $first([
                TrackStatus::Pickup->value,
                TrackStatus::Handover->value,
                TrackStatus::Stuffing->value,
                TrackStatus::DeliveryToPort->value,
                TrackStatus::Stacking->value,
            ]),
            'onboard' => $first([
                TrackStatus::UnitLoading->value,
                TrackStatus::OnShip->value,
                TrackStatus::VesselDepart->value,
            ]),
            'arrived' => $first([
                TrackStatus::VesselArrival->value,
                TrackStatus::Unloading->value,
            ]),
            'deliv' => $last(TrackStatus::Delivered->value),
        ];
    }

    public function diffDaysNullable($from, $to): ?int
    {
        if (! $from || ! $to) {
            return null;
        }

        return Carbon::parse($from)->startOfDay()->diffInDays(Carbon::parse($to)->startOfDay());
    }

    public function evaluateManadoKpi(Shipment $shipment): array
    {
        if (! $this->isManadoKpiTarget($shipment)) {
            return ['applies' => false];
        }

        $priority = $shipment->priority === 'urgent' ? 'urgent' : 'normal';
        $t = $this->getManadoThresholds();
        $ms = $this->getMilestoneTimes($shipment);

        $dw = $this->diffDaysNullable($ms['pickup'], $ms['onboard']);
        $sa = $this->diffDaysNullable($ms['onboard'], $ms['arrived']);
        $dr = $this->diffDaysNullable($ms['arrived'], $ms['deliv']);
        $tt = ($dw !== null && $sa !== null && $dr !== null) ? ($dw + $sa + $dr) : null;

        $okDw = is_null($dw) ? null : $dw <= ($t['dwelling_days'] ?? PHP_INT_MAX);
        $okSa = is_null($sa) ? null : $sa <= ($t['sailing_days'] ?? PHP_INT_MAX);
        $okDr = is_null($dr) ? null : $dr <= ($t['dooring_days'] ?? PHP_INT_MAX);
        $okTt = is_null($tt) ? null : $tt <= (($t['total_days'][$priority] ?? null) ?? PHP_INT_MAX);

        return [
            'applies' => true,
            'priority' => $priority,
            'summary' => [
                'dwelling' => [
                    'actual' => $dw,
                    'limit' => $t['dwelling_days'],
                    'status' => is_null($okDw) ? 'PENDING' : ($okDw ? 'OK' : 'LATE'),
                ],
                'sailing' => [
                    'actual' => $sa,
                    'limit' => $t['sailing_days'],
                    'status' => is_null($okSa) ? 'PENDING' : ($okSa ? 'OK' : 'LATE'),
                ],
                'dooring' => [
                    'actual' => $dr,
                    'limit' => $t['dooring_days'],
                    'status' => is_null($okDr) ? 'PENDING' : ($okDr ? 'OK' : 'LATE'),
                ],
                'total' => [
                    'actual' => $tt,
                    'limit' => $t['total_days'][$priority] ?? null,
                    'status' => is_null($okTt) ? 'PENDING' : ($okTt ? 'OK' : 'LATE'),
                ],
            ],
            'badge' => is_null($okTt) ? 'Pending' : ($okTt ? 'On Time' : 'Late'),
        ];
    }

    public function manadoSummaryText(Shipment $shipment): ?string
    {
        $ev = $this->evaluateManadoKpi($shipment);
        if (! ($ev['applies'] ?? false)) {
            return null;
        }

        $s = $ev['summary'];
        $p = $ev['priority'] ?? 'normal';

        if ($p === 'urgent') {
            return sprintf(
                'Dw %s/%s | Dor %s/%s | Total %s/%s',
                $s['dwelling']['actual'] ?? '-',
                $s['dwelling']['limit'] ?? '-',
                $s['dooring']['actual'] ?? '-',
                $s['dooring']['limit'] ?? '-',
                $s['total']['actual'] ?? '-',
                $s['total']['limit'] ?? '-',
            );
        }

        return sprintf(
            'Total %s/%s | Dw %s/%s | Sai %s/%s | Dor %s/%s',
            $s['total']['actual'] ?? '-',
            $s['total']['limit'] ?? '-',
            $s['dwelling']['actual'] ?? '-',
            $s['dwelling']['limit'] ?? '-',
            $s['sailing']['actual'] ?? '-',
            $s['sailing']['limit'] ?? '-',
            $s['dooring']['actual'] ?? '-',
            $s['dooring']['limit'] ?? '-',
        );
    }

    public function compare(?float $actual, ?float $target): array
    {
        if (is_null($actual) || is_null($target)) {
            return ['status' => 'unknown', 'diff' => null];
        }

        $diff = $actual - $target;

        if ($diff <= 0) {
            return ['status' => 'ok', 'diff' => round($diff, 2)];
        }

        return ['status' => 'late', 'diff' => round($diff, 2)];
    }

    public function getPlannedKpi(): array
    {
        return [
            'dwelling' => config('kpi.manado.thresholds.dwelling_days', 6),
            'sailing' => config('kpi.manado.thresholds.sailing_days', 10),
            'dooring' => config('kpi.manado.thresholds.dooring_days', 3),
            'total' => config('kpi.manado.thresholds.total_days.normal', 19),
        ];
    }
}
