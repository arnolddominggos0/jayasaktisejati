<?php

namespace App\Services\Monitoring;

use App\Models\ShippingSchedule;
use Illuminate\Support\Carbon;

/**
 * @deprecated Voyage-centric architecture. MonitoringKapalTam kini menggunakan
 *             Voyage::whereHas('shipments') langsung. Service ini tidak dipanggil.
 */
class TamMonitoringQueryService
{
    public function getRows(string $period, string $filter)
    {
        $dt = Carbon::createFromFormat('Y-m', $period)->startOfMonth();

        $query = ShippingSchedule::query()
            ->with([
                'voyage.vessel',
                'voyage.pol',
                'voyage.pod',
                'voyage.sailingSla',
                'vesselChecks',
            ])
            ->whereDate('period_month', $dt->toDateString());

        return $this->applyFilter($query, $filter)->get();
    }

    protected function applyFilter($query, string $filter)
    {
        return match ($filter) {
            'ongoing' => $query->whereHas(
                'voyage',
                fn($q) =>
                $q->whereNotNull('atd_at')
                    ->whereNull('ata_at')
            ),

            'risk' => $query->whereHas(
                'voyage',
                fn($q) =>
                $q->whereNotNull('atd_at')
                    ->whereNull('ata_at')
                    ->where('actual_sailing_days', '>=', 8)
            ),

            'late' => $query->whereHas(
                'voyage',
                fn($q) =>
                $q->whereNotNull('ata_at')
                    ->where('actual_sailing_days', '>', 10)
            ),

            default => $query,
        };
    }
}
