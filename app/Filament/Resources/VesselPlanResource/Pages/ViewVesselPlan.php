<?php

namespace App\Filament\Resources\VesselPlanResource\Pages;

use App\Filament\Resources\VesselPlanResource;
use App\Filament\Resources\VesselPlanResource\Widgets\VesselPlanAnalysis;
use Filament\Resources\Pages\ViewRecord;

class ViewVesselPlan extends ViewRecord
{
    protected static string $resource = VesselPlanResource::class;

    protected function getHeaderWidgets(): array
    {
        return [VesselPlanAnalysis::class];
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Eager load semua relasi yang dibutuhkan oleh 3 tab
        $this->record->loadMissing([
            'items.vessel',
            'items.shippingLine',
            'items.voyage',
            'snapshots',        // untuk draftSnapshot() & finalSnapshot() tanpa query ulang
        ]);
    }
}
