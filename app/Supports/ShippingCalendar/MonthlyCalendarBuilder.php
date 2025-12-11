<?php

namespace App\Supports\ShippingCalendar;

use Illuminate\Support\Carbon;

class MonthlyCalendarBuilder
{
    protected CalendarDataFetcher $fetcher;
    protected VoyageMetaExtractor $extractor;
    protected VoyageGroupingService $grouper;
    protected CalendarLaneBuilder $laneBuilder;
    protected KpiCalculator $kpiCalculator;

    public function __construct()
    {
        $this->fetcher = new CalendarDataFetcher();
        $this->extractor = new VoyageMetaExtractor();
        $this->grouper = new VoyageGroupingService();
        $this->laneBuilder = new CalendarLaneBuilder();
        $this->kpiCalculator = new KpiCalculator();
    }

    /**
     * forMonth now accepts optional pol/pod so widget can pass user-selected route.
     *
     * @param int $year
     * @param int $month
     * @param string|null $pol upper-case code
     * @param string|null $pod upper-case code
     * @return array
     */
    public function forMonth(int $year, int $month, ?string $pol = null, ?string $pod = null): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        // fetch schedules (CalendarDataFetcher will enrich vessel_code & meta)
        $rows = $this->fetcher->fetch($start, $end, $pol, $pod);

        $rowsArray = is_array($rows)
            ? $rows
            : (is_object($rows) && method_exists($rows, 'all') ? $rows->all() : (array) $rows);

        // Grouping/extraction should use the enriched models (meta + vessel_code)
        $groups = $this->grouper->group($rows, $this->extractor);

        // Build lanes (CalendarLaneBuilder should pick meta/vessel_code from the grouped data)
        $calendar = $this->laneBuilder->build($groups, $start, $end);

        $kpiRaw = $this->kpiCalculator->calculate($rowsArray, $start, $end);

        if (is_object($kpiRaw) && method_exists($kpiRaw, 'toArray')) {
            $kpi = $kpiRaw->toArray();
        } elseif (is_object($kpiRaw)) {
            $kpi = json_decode(json_encode($kpiRaw), true);
        } elseif (is_array($kpiRaw)) {
            $kpi = $kpiRaw;
        } else {
            $kpi = (array) $kpiRaw;
        }

        $days = [];
        for ($i = 1; $i <= ($calendar['days_count'] ?? $start->daysInMonth); $i++) {
            $d = $start->copy()->day($i);
            $days[] = [
                'n' => $i,
                'date' => $d->toDateString(),
                'isWeekend' => $d->isWeekend(),
                'dow' => $d->isoFormat('dd'),
            ];
        }

        $monthLabel = $start->translatedFormat('F Y');

        $totalPlan = 0;
        foreach ($rows as $r) {
            $totalPlan += (int)($r->cargo_plan ?? 0);
        }

        return [
            'month_label' => $monthLabel,
            'days' => $days,
            'days_count' => $calendar['days_count'] ?? $start->daysInMonth,
            'lanes' => $calendar['lanes'] ?? [
                'plan_etd' => 'ETD (Plan)',
                'plan_eta' => 'ETA (Plan)',
                'act_atd' => 'ATD (Aktual)',
                'act_ata' => 'ATA (Aktual)',
                'sum_atd' => 'Vol. ATD (Total)',
            ],
            'lane_types' => $calendar['lane_types'] ?? [],
            'bucket' => $calendar['bucket'] ?? [],
            'sailing_bg' => $calendar['sailing_bg'] ?? [],
            'bars' => $calendar['bars'] ?? [],
            'total_plan' => $totalPlan,
            'kpi' => $kpi,
            'summary' => [
                'voyage_total' => $kpi['total'] ?? 0,
                'on_time' => $kpi['on_time'] ?? 0,
                'late' => $kpi['late'] ?? 0,
                'urgent' => $kpi['urgent'] ?? 0,
                'avg_lead_time' => $kpi['avg_lead_time'] ?? null,
            ],
            'month_options' => $this->monthOptions(),
            'year_options' => $this->yearOptions(),
            'today' => Carbon::now()->toDateString(),
        ];
    }

    protected function monthOptions(): array
    {
        $opts = [];
        foreach (range(1, 12) as $m) {
            $opts[$m] = Carbon::create(null, $m, 1)->translatedFormat('F');
        }
        return $opts;
    }

    protected function yearOptions(): array
    {
        return range(now()->year - 2, now()->year + 2);
    }
}
