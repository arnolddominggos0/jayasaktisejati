<?php

namespace App\Filament\Pages\Dashboard\Widgets;

use Filament\Widgets\Widget;

class ActiveArmadaWidget extends Widget
{
    protected static string $view = 'filament.widgets.active-armada-widget';
    protected static ?string $heading = 'Armada Aktif';
    protected static ?string $pollingInterval = '60s';
    protected int|string|array $columnSpan = ['xl' => 1];

    public function getViewData(): array
    {
        $rows = [];

        if (class_exists(\App\Models\Armada::class)) {
            $rows = \App\Models\Armada::query()
                ->whereIn('status', ['on_duty', 'operational'])
                ->with(['currentAssignment.shipment'])
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn($a) => [
                    'name' => $a->name ?? ($a->plate_no ?? 'Armada'),
                    'route' => optional($a->currentAssignment?->shipment)
                        ? (($a->currentAssignment->shipment->origin ?? '—') . ' → ' . ($a->currentAssignment->shipment->destination ?? '—'))
                        : '—',
                    'eta' => optional($a->currentAssignment?->shipment?->eta)?->format('d M, H:i') ?? null,
                    'badge' => $a->status,
                ])
                ->toArray();
        }

        return compact('rows');
    }
}
