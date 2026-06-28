<?php

namespace App\Services\Monitoring;

use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Enums\TrackStatus;
use App\Models\Shipment;
use App\ViewModels\Monitoring\ExceptionChipData;

final class ExceptionEvaluator
{
    public function evaluate(Shipment $shipment): array
    {
        $exceptions = [];

        if ($this->isHold($shipment)) {
            $exceptions[] = new ExceptionChipData(
                type: 'hold',
                label: 'Hold',
                severity: 'danger',
                detail: optional($shipment->latestTrack)->note,
                icon: 'heroicon-o-exclamation-triangle',
            );
        }

        if ($this->isDelay($shipment)) {
            $overdueDays = $shipment->eta
                ? (int) now()->startOfDay()->diffInDays($shipment->eta->startOfDay(), false) * -1
                : null;
            $exceptions[] = new ExceptionChipData(
                type: 'delay',
                label: 'Terlambat',
                severity: 'danger',
                detail: $overdueDays !== null ? "{$overdueDays} hari melewati ETA" : null,
                icon: 'heroicon-o-clock',
            );
        }

        if ($this->hasMissingVoyage($shipment)) {
            $exceptions[] = new ExceptionChipData(
                type: 'missing_voyage',
                label: 'Voyage Belum Assign',
                severity: 'warning',
                icon: 'heroicon-o-paper-airplane',
            );
        }

        if ($this->hasDemurrage($shipment)) {
            $exceptions[] = new ExceptionChipData(
                type: 'demurrage',
                label: 'Demurrage',
                severity: 'warning',
                icon: 'heroicon-o-rectangle-group',
            );
        }

        $ngCount = $this->countNgItems($shipment);
        if ($ngCount > 0) {
            $exceptions[] = new ExceptionChipData(
                type: 'ng',
                label: 'NG',
                severity: 'warning',
                icon: 'heroicon-o-wrench-screwdriver',
                count: $ngCount,
            );
        }

        if ($this->hasPdiPending($shipment)) {
            $exceptions[] = new ExceptionChipData(
                type: 'pdi_pending',
                label: 'PDI Pending',
                severity: 'warning',
                icon: 'heroicon-o-clipboard-document-check',
            );
        }

        return $exceptions;
    }

    public static function types(): array
    {
        return config('monitoring.exception_types', [
            'delay', 'ng', 'hold', 'demurrage', 'missing_voyage', 'pdi_pending',
        ]);
    }

    private function isHold(Shipment $shipment): bool
    {
        $current = $shipment->currentTrackStatus();

        return $current === TrackStatus::Hold;
    }

    private function isDelay(Shipment $shipment): bool
    {
        if ($shipment->isHistorical()) {
            return false;
        }

        if (!$shipment->eta) {
            return false;
        }

        return now()->isAfter($shipment->eta);
    }

    private function hasMissingVoyage(Shipment $shipment): bool
    {
        $mode = $shipment->mode instanceof ShipmentMode
            ? $shipment->mode
            : ShipmentMode::tryFrom((string) $shipment->mode);

        if ($mode !== ShipmentMode::Sea) {
            return false;
        }

        if ($shipment->isHistorical()) {
            return false;
        }

        return empty($shipment->voyage_id);
    }

    private function hasDemurrage(Shipment $shipment): bool
    {
        $demurrageDays = (int) config('monitoring.demurrage_days', 7);

        $tracks = $shipment->relationLoaded('tracks') ? $shipment->tracks : collect();

        $stackingTrack = $tracks->first(fn($t) => (
            $t->tracked_at !== null &&
            ($t->status instanceof TrackStatus
                ? $t->status === TrackStatus::Stacking
                : $t->status === TrackStatus::Stacking->value)
        ));

        if (!$stackingTrack) {
            return false;
        }

        // Only flag demurrage if unit is still pre-loading (hasn't boarded yet)
        $loadingTrack = $tracks->first(fn($t) => (
            $t->tracked_at !== null &&
            ($t->status instanceof TrackStatus
                ? $t->status === TrackStatus::UnitLoading
                : $t->status === TrackStatus::UnitLoading->value)
        ));

        if ($loadingTrack) {
            return false;
        }

        $daysSinceStacking = (int) $stackingTrack->tracked_at->startOfDay()->diffInDays(now()->startOfDay());

        return $daysSinceStacking > $demurrageDays;
    }

    private function countNgItems(Shipment $shipment): int
    {
        $units = $shipment->relationLoaded('units') ? $shipment->units : collect();
        $total = 0;

        foreach ($units as $unit) {
            $inspections = $unit->relationLoaded('inspections') ? $unit->inspections : collect();
            foreach ($inspections as $insp) {
                $items = $insp->relationLoaded('items') ? $insp->items : collect();
                $total += $items->where('result', 'ng')->count();
            }
        }

        return $total;
    }

    private function hasPdiPending(Shipment $shipment): bool
    {
        if ($shipment->isHistorical()) {
            return false;
        }

        $units = $shipment->relationLoaded('units') ? $shipment->units : collect();

        foreach ($units as $unit) {
            $inspections = $unit->relationLoaded('inspections') ? $unit->inspections : collect();

            if ($inspections->isEmpty()) {
                continue;
            }

            $hasPending = $inspections->contains(fn($insp) => $insp->submitted_at === null);
            if ($hasPending) {
                return true;
            }
        }

        return false;
    }
}