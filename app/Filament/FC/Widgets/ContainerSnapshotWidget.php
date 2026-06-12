<?php

namespace App\Filament\FC\Widgets;

use Filament\Widgets\StatsOverviewWidget as Widget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

/**
 * Snapshot Container Readiness HARI INI — untuk Dashboard FC.
 *
 * Sumber: container_readiness_sessions WHERE session_date = today().
 * Demand planning only — bukan container tracking.
 * 3 card: Need, Available, Status.
 */
class ContainerSnapshotWidget extends Widget
{
    protected static ?string $pollingInterval = '60s';
    protected int|string|array $columnSpan    = 'full';

    protected function getStats(): array
    {
        $today = now()->toDateString();

        $row = DB::table('container_readiness_sessions')
            ->whereDate('session_date', $today)
            ->select('unit_count', 'container_need', 'container_available', 'gap', 'summary_sufficient')
            ->first();

        $hasData   = $row !== null;
        $unitCount = $hasData ? (int) $row->unit_count          : 0;
        $need      = $hasData ? (int) $row->container_need      : 0;
        $available = $hasData ? (int) $row->container_available : 0;
        $gap       = $hasData ? (int) $row->gap                 : 0;
        $isReady   = $hasData && (bool) $row->summary_sufficient;

        // ── Status card ───────────────────────────────────────────────────────
        if (! $hasData) {
            $statusLabel = 'Belum Ada Data';
            $statusColor = 'gray';
            $statusDesc  = 'Input container readiness hari ini';
        } elseif ($isReady) {
            $statusLabel = 'READY';
            $statusColor = 'success';
            $statusDesc  = "Surplus +" . $gap . " container";
        } else {
            $statusLabel = 'NOT READY';
            $statusColor = 'danger';
            $statusDesc  = "Kurang " . abs($gap) . " container";
        }

        $availableColor = ! $hasData ? 'gray'
            : ($isReady ? 'success' : ($available >= (int) ceil($need * 0.6) ? 'warning' : 'danger'));

        return [
            Stat::make('Container Need', $hasData ? $need . ' unit' : '—')
                ->description($hasData ? "{$unitCount} unit kendaraan hari ini" : 'Belum ada data hari ini')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color($hasData ? 'primary' : 'gray'),

            Stat::make('Container Available', $hasData ? $available . ' unit' : '—')
                ->description($hasData
                    ? ($gap >= 0 ? "Surplus +{$gap}" : "Kurang " . abs($gap))
                    : 'Belum ada data')
                ->descriptionIcon('heroicon-m-cube-transparent')
                ->color($availableColor),

            Stat::make('Container Status', $statusLabel)
                ->description($statusDesc)
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($statusColor),
        ];
    }
}
