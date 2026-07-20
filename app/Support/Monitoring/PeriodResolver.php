<?php

namespace App\Support\Monitoring;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Resolves the `period` filter (format: 'YYYY-MM') to a half-open date range
 * [start of month, start of next month) and generates the rolling option
 * list for the Period selector. Shared by UnitMonitoringQueryBuilder,
 * ExceptionCountQueryBuilder, and WorkspaceSummaryQueryBuilder so the table
 * and the header KPIs always agree on what "this period" means.
 */
final class PeriodResolver
{
    public static function default(): string
    {
        return now()->format('Y-m');
    }

    public static function isValid(?string $period): bool
    {
        return is_string($period) && (bool) preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period);
    }

    public static function normalize(?string $period): string
    {
        return self::isValid($period) ? $period : self::default();
    }

    /**
     * @return array{0: Carbon, 1: Carbon} [start of month, start of next month)
     */
    public static function bounds(?string $period): array
    {
        $start = Carbon::createFromFormat('Y-m-d', self::normalize($period) . '-01')->startOfDay();
        $end   = $start->copy()->addMonthNoOverflow();

        return [$start, $end];
    }

    /**
     * Apply the period range to a query, mirroring AgeCalculator/sort's
     * "age start" convention: prefer requested_at, fall back to created_at
     * when requested_at is null. Bound parameters on un-wrapped columns,
     * so existing indexes on requested_at/created_at remain usable.
     *
     * Shared by every query builder that needs period scoping (table rows,
     * exception counts, workspace summary) so they can never disagree about
     * what "this period" means.
     */
    public static function applyTo(Builder $query, ?string $period, string $table = 'shipments'): void
    {
        [$start, $end] = self::bounds($period);

        $query->where(function (Builder $where) use ($start, $end, $table) {
            $where
                ->where(function (Builder $w) use ($start, $end, $table) {
                    $w->whereNotNull("{$table}.requested_at")
                        ->where("{$table}.requested_at", '>=', $start)
                        ->where("{$table}.requested_at", '<', $end);
                })
                ->orWhere(function (Builder $w) use ($start, $end, $table) {
                    $w->whereNull("{$table}.requested_at")
                        ->where("{$table}.created_at", '>=', $start)
                        ->where("{$table}.created_at", '<', $end);
                });
        });
    }

    /**
     * Rolling window of selectable periods, current month first. Scales
     * automatically as time passes — no hardcoded month list to maintain.
     *
     * @return array<string,string> 'YYYY-MM' => display label (e.g. "Juni 2026")
     */
    public static function options(int $lookbackMonths = 12): array
    {
        $options = [];
        $cursor  = now()->startOfMonth();

        for ($i = 0; $i < $lookbackMonths; $i++) {
            $options[$cursor->format('Y-m')] = ucfirst($cursor->translatedFormat('F Y'));
            $cursor = $cursor->copy()->subMonthNoOverflow();
        }

        return $options;
    }
}
