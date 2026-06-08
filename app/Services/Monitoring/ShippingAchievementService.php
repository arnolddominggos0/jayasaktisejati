<?php

namespace App\Services\Monitoring;

use App\Models\Voyage;
use App\Models\SlaResult;

class ShippingAchievementService
{
    public function summary(int $year, int $month): array
    {
        $voyages = Voyage::query()
            ->whereYear('period_month', $year)
            ->whereMonth('period_month', $month)
            ->whereHas('shipments', fn($q) => $q->where('status', '!=', 'cancelled'))
            ->get();

        $totalVoyage = $voyages->count();

        $departed = $voyages->filter(fn ($v) => $v->atd_at !== null);
        $departedTotal = $departed->count();

        $otdOk = $departed
            ->filter(fn ($v) => $v->otd_status?->value === 'ontime')
            ->count();

        $arrived = $voyages->filter(fn ($v) => $v->ata_at !== null);
        $arrivedTotal = $arrived->count();

        $otaOk = $arrived
            ->filter(fn ($v) => $v->ota_status?->value === 'ontime')
            ->count();

        $berthed = $voyages->filter(fn ($v) => $v->atb_at !== null);
        $berthedTotal = $berthed->count();

        $otbOk = $berthed
            ->filter(fn ($v) => $v->otb_status?->value === 'ontime')
            ->count();

        $slaResults = SlaResult::query()
            ->where('activity', 'sailing')
            ->whereHas('voyage', function ($q) use ($year, $month) {
                $q->whereYear('period_month', $year)
                  ->whereMonth('period_month', $month);
            })
            ->get();

        $slaTotal = $slaResults->count();
        $slaOk = $slaResults
            ->filter(fn($r) => in_array($r->getRawOriginal('status'), ['ontime', 'risk'], true))
            ->count();

        $avgDepartureDelay = $departed
            ->map(function ($v) {
                if (!$v->etd || !$v->atd_at) return 0;
                $hours = max(0, $v->atd_at->diffInHours($v->etd, false) * -1);
                return $hours > 0 ? (int) ceil($hours / 24) : 0;
            })
            ->avg();

        $delayReasonCounts = $voyages
            ->where('is_delayed', true)
            ->pluck('manual_delay_reason')
            ->filter()
            ->map(fn($r) => $r->label())
            ->countBy()
            ->sortDesc();

        $topDelayReason = $delayReasonCounts->keys()->first();

        return [
            'total_voyage' => $totalVoyage,
            'otd' => $this->formatMetric($otdOk, $departedTotal),
            'ota' => $this->formatMetric($otaOk, $arrivedTotal),
            'otb' => $this->formatMetric($otbOk, $berthedTotal),
            'sla' => $this->formatMetric($slaOk, $slaTotal),
            'rata_rata_delay_berangkat' => round($avgDepartureDelay, 2),
            'penyebab_terbanyak' => $topDelayReason,
        ];
    }

    protected function formatMetric(int $ok, int $total): array
    {
        if ($total === 0) {
            return [
                'ok_percent' => 0,
                'ng_percent' => 0,
                'total' => 0,
                'ok' => 0,
                'ng' => 0,
            ];
        }

        $okPercent = round(($ok / $total) * 100, 2);

        return [
            'ok_percent' => $okPercent,
            'ng_percent' => round(100 - $okPercent, 2),
            'total' => $total,
            'ok' => $ok,
            'ng' => $total - $ok,
        ];
    }
}