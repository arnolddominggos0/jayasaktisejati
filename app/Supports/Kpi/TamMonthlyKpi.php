<?php

namespace App\Supports\Kpi;

use App\Models\SlaResult;
use Illuminate\Support\Carbon;

class TamMonthlyKpi
{
    public static function calculate(
        Carbon $month,
        int $polId,
        int $podId
    ): array {
        $from = $month->copy()->startOfMonth();
        $to   = $month->copy()->endOfMonth();

        $rows = SlaResult::query()
            ->where('activity', 'sailing')
            ->whereBetween('end_at', [$from, $to])
            ->whereHas(
                'voyage',
                fn($q) =>
                $q->where('pol_id', $polId)
                    ->where('pod_id', $podId)
            )
            ->get();

        $total   = $rows->count();
        $onTime  = $rows->where('status', 'on_time')->count();
        $late    = $rows->where('status', 'late')->count();

        $avgDays = $rows->avg('actual_days');

        $slaPct = $total > 0
            ? round(($onTime / $total) * 100, 2)
            : null;

        return [
            'period'        => $month->format('F Y'),
            'total_voyage'  => $total,
            'on_time'       => $onTime,
            'late'          => $late,
            'sla_percent'   => $slaPct,
            'avg_sailing'   => $avgDays ? round($avgDays, 2) : null,
            'status'        => self::label($slaPct),
        ];
    }

    protected static function label(?float $pct): string
    {
        if ($pct === null) return 'NO DATA';
        if ($pct >= 95) return 'EXCELLENT';
        if ($pct >= 90) return 'GOOD';
        if ($pct >= 80) return 'WARNING';
        return 'CRITICAL';
    }
}
