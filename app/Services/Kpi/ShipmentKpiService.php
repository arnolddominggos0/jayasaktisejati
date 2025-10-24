<?php

namespace App\Services\Kpi;

use App\Models\Shipment;
use App\Enums\TrackStatus;

class ShipmentKpiService
{
    public function milestones(Shipment $s): array
    {
        return $s->milestoneTimes();
    }

    public function dwellingDays(Shipment $s): ?int
    {
        $m = $this->milestones($s);
        return $this->diff($m['pickup'] ?? $s->requested_at, $m['onboard'] ?? null);
    }

    public function sailingDays(Shipment $s): ?int
    {
        $m = $this->milestones($s);
        return $this->diff($m['onboard'] ?? null, $m['arrived'] ?? null);
    }

    public function dooringDays(Shipment $s): ?int
    {
        $m = $this->milestones($s);
        return $this->diff($m['arrived'] ?? null, $m['deliv'] ?? null);
    }

    public function leadTimeDays(Shipment $s): ?int
    {
        $dw = $this->dwellingDays($s);
        $sa = $this->sailingDays($s);
        $dr = $this->dooringDays($s);
        return is_null($dw) || is_null($sa) || is_null($dr) ? null : $dw + $sa + $dr;
    }

    public function onTimeDeparture(Shipment $s, int $toleranceHours = 0): ?bool
    {
        $etd = $s->etd;
        if (!$etd) return null;

        $atd = $s->tracks()
            ->whereIn('status', [TrackStatus::VesselATD->value, TrackStatus::VesselDepart->value])
            ->orderByRaw('COALESCE(actual_at, tracked_at, created_at) asc')
            ->value('actual_at');

        if (!$atd) return null;

        $allowed = \Illuminate\Support\Carbon::parse($etd)->addHours($toleranceHours);
        return \Illuminate\Support\Carbon::parse($atd)->lessThanOrEqualTo($allowed);
    }

    public function evaluateAgainstRules(Shipment $s, array $rules): array
    {
        $priority = ($s->priority === 'urgent') ? 'urgent' : 'normal';

        $dw = $this->dwellingDays($s);
        $sa = $this->sailingDays($s);
        $dr = $this->dooringDays($s);
        $tt = $this->leadTimeDays($s);

        $okDw = is_null($dw) ? null : $dw <= ($rules['dwelling_days'] ?? PHP_INT_MAX);
        $okSa = is_null($sa) ? null : $sa <= ($rules['sailing_days']  ?? PHP_INT_MAX);
        $okDr = is_null($dr) ? null : $dr <= ($rules['dooring_days']  ?? PHP_INT_MAX);
        $okTt = is_null($tt) ? null : $tt <= (($rules['total_days'][$priority] ?? null) ?? PHP_INT_MAX);

        return [
            'dwelling' => ['actual' => $dw, 'limit' => $rules['dwelling_days'] ?? null,                 'ok' => $okDw],
            'sailing'  => ['actual' => $sa, 'limit' => $rules['sailing_days']  ?? null,                 'ok' => $okSa],
            'dooring'  => ['actual' => $dr, 'limit' => $rules['dooring_days']  ?? null,                 'ok' => $okDr],
            'total'    => ['actual' => $tt, 'limit' => $rules['total_days'][$priority] ?? null,         'ok' => $okTt],
        ];
    }

    protected function diff($from, $to): ?int
    {
        if (!$from || !$to) return null;
        $a = \Illuminate\Support\Carbon::parse($from)->startOfDay();
        $b = \Illuminate\Support\Carbon::parse($to)->startOfDay();
        return $a->diffInDays($b);
    }
}
