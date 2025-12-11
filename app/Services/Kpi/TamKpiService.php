<?php

namespace App\Services\Kpi;

use App\Models\Shipment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TamKpiService
{
    public function __construct(
        protected ShipmentKpiService $shipmentKpi
    ) {}

    public function shipmentsQueryForPeriod(
        int $year,
        int $month,
        ?int $branchId = null
    ): Builder {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end   = (clone $start)->endOfMonth()->endOfDay();

        $q = Shipment::query()
            ->manadoKpiTarget()
            ->whereBetween('requested_at', [$start, $end]);

        if ($branchId) {
            $q->where('branch_id', $branchId);
        }

        return $q->with(['tracks', 'customer']);
    }

    public function summaryForPeriod(
        int $year,
        int $month,
        ?int $branchId = null
    ): array {
        $shipments = $this->shipmentsQueryForPeriod($year, $month, $branchId)->get();

        $total = $shipments->count();

        $metrics = [
            'dwelling' => ['ontime' => 0, 'late' => 0, 'pending' => 0],
            'sailing'  => ['ontime' => 0, 'late' => 0, 'pending' => 0],
            'dooring'  => ['ontime' => 0, 'late' => 0, 'pending' => 0],
            'total'    => ['ontime' => 0, 'late' => 0, 'pending' => 0],
        ];

        foreach ($shipments as $s) {
            $ev = $this->shipmentKpi->evaluateManado($s);

            if (! ($ev['applies'] ?? false)) {
                continue;
            }

            $sum = $ev['summary'];

            foreach (['dwelling', 'sailing', 'dooring', 'total'] as $key) {
                $status = $sum[$key]['status'] ?? 'PENDING';

                if ($status === 'OK') {
                    $metrics[$key]['ontime']++;
                } elseif ($status === 'LATE') {
                    $metrics[$key]['late']++;
                } else {
                    $metrics[$key]['pending']++;
                }
            }
        }

        $percent = function (int $x) use ($total): ?float {
            if ($total === 0) {
                return null;
            }

            return round($x / $total * 100, 2);
        };

        $result = [
            'year'            => $year,
            'month'           => $month,
            'total_shipments' => $total,
            'metrics'         => [],
        ];

        foreach ($metrics as $key => $row) {
            $result['metrics'][$key] = [
                'ontime'      => $row['ontime'],
                'late'        => $row['late'],
                'pending'     => $row['pending'],
                'ontime_pct'  => $percent($row['ontime']),
                'late_pct'    => $percent($row['late']),
                'pending_pct' => $percent($row['pending']),
            ];
        }

        return $result;
    }

    public function breakdownByCustomer(
        int $year,
        int $month,
        ?int $branchId = null
    ): Collection {
        $shipments = $this->shipmentsQueryForPeriod($year, $month, $branchId)->get();

        return $shipments
            ->groupBy('customer_id')
            ->map(function (Collection $rows, $customerId) {
                $first   = $rows->first();
                $name    = optional($first->customer)->name ?? 'Unknown';
                $total   = $rows->count();
                $ontime  = 0;
                $late    = 0;
                $pending = 0;

                foreach ($rows as $s) {
                    $ev = $this->shipmentKpi->evaluateManado($s);

                    if (! ($ev['applies'] ?? false)) {
                        continue;
                    }

                    $status = $ev['summary']['total']['status'] ?? 'PENDING';

                    if ($status === 'OK') {
                        $ontime++;
                    } elseif ($status === 'LATE') {
                        $late++;
                    } else {
                        $pending++;
                    }
                }

                return [
                    'customer_id'   => (int) $customerId,
                    'customer_name' => $name,
                    'total'         => $total,
                    'ontime'        => $ontime,
                    'late'          => $late,
                    'pending'       => $pending,
                    'ontime_pct'    => $total > 0 ? round($ontime / $total * 100, 2) : null,
                    'late_pct'      => $total > 0 ? round($late / $total * 100, 2) : null,
                    'pending_pct'   => $total > 0 ? round($pending / $total * 100, 2) : null,
                ];
            })
            ->values()
            ->sortByDesc('ontime_pct');
    }
}
