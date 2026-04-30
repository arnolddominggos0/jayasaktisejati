<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

trait HasMonthlyOverlap
{
    public function scopeFinal(Builder $q): Builder
    {
        return $q->where('state', 'final');
    }

    public function scopeOverlapsRange(Builder $q, Carbon $start, Carbon $end): Builder
    {
        return $q->where(function ($qq) use ($start, $end) {
            $qq->whereBetween('etd', [$start, $end])
                ->orWhereBetween('eta', [$start, $end])
                ->orWhere(function ($qqq) use ($start, $end) {
                    $qqq->where('etd', '<=', $start)->where('eta', '>=', $end);
                });
        });
    }

    public function scopeOverlapsMonth(Builder $q, int $year, int $month, string $tz = 'Asia/Jakarta'): Builder
    {
        $start = Carbon::createFromDate($year, $month, 1, $tz)->startOfDay();
        $end   = (clone $start)->endOfMonth()->endOfDay();
        return $this->scopeOverlapsRange($q, $start, $end);
    }
}
