<?php

namespace App\Services\Kpi;

use App\Models\Voyage;
use App\Models\SlaRule;
use Illuminate\Support\Carbon;

class TamSailingKpiService
{
    public function summaryForPeriod(int $year, int $month): array
    {
        $rows = Voyage::query()
            ->whereYear('etd', $year)
            ->whereMonth('etd', $month)
            ->whereHas('shipments', fn($q) => $q->where('status', '!=', 'cancelled'))
            ->with(['pol', 'pod'])
            ->get()
            ->map(fn ($v) => $this->evaluateVoyage($v))
            ->filter()
            ->values();

        return [
            'year'    => $year,
            'month'   => $month,
            'total'   => $rows->count(),
            'ontime'  => $rows->where('status', 'ONTIME')->count(),
            'late'    => $rows->where('status', 'LATE')->count(),
            'ongoing' => $rows->where('status', 'ONGOING')->count(),
            'rows'    => $rows,
        ];
    }

    protected function evaluateVoyage(Voyage $v): ?array
    {
        if (! $v->atd_at) return null;

        $rule = SlaRule::query()
            ->where('mode', 'sea')
            ->where('activity', 'sailing')
            ->where('pol_id', $v->pol_id)
            ->where('pod_id', $v->pod_id)
            ->where('is_active', true)
            ->first();

        if (! $rule) return null;   

        $actual = $this->actualDays($v);

        $status = $v->ata_at
            ? ($actual <= $rule->target_days ? 'ONTIME' : 'LATE')
            : 'ONGOING';

        return [
            'voyage_id'   => $v->id,
            'route'       => "{$v->pol?->code} → {$v->pod?->code}",
            'target_days' => $rule->target_days,
            'actual_days' => $actual,
            'status'      => $status,
        ];
    }

    protected function actualDays(Voyage $v): float
    {
        return round(
            Carbon::parse($v->atd_at)
                ->diffInSeconds($v->ata_at ?? now()) / 86400,
            2
        );
    }
}
