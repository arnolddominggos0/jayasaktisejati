<?php

namespace App\Filament\Resources\ShipmentTrackingResource\Pages;

use App\Filament\Resources\ShipmentTrackingResource;
use App\Models\Shipment;
use App\Models\ShipmentTrack;
use Filament\Resources\Pages\Page;

class TrackingDashboard extends Page
{
    protected static string $resource = ShipmentTrackingResource::class;

    protected static string $view = 'filament.resources.shipment-tracking-resource.pages.tracking-dashboard';

    protected function getViewData(): array
    {
        $tab = request('tab', 'semua');
        $q   = request('q');

        $base = Shipment::query()
            ->with([
                'customer',
                'originCity',
                'destinationCity',
            ])
            ->withCount('tracks')
            ->with(['tracks' => fn($q) => $q->latest('tracked_at')->limit(1)]); 

        if ($q) {
            $base->where(function ($qq) use ($q) {
                $qq->where('code', 'ilike', "%{$q}%")
                    ->orWhereHas('customer', fn($c) => $c->where('name', 'ilike', "%{$q}%"))
                    ->orWhereHas('destinationCity', fn($c) => $c->where('name', 'ilike', "%{$q}%"));
            });
        }

        if ($tab === 'laut')   $base->where('mode', \App\Enums\ShipmentMode::Sea);
        if ($tab === 'darat')  $base->where('mode', \App\Enums\ShipmentMode::Land);
        if ($tab === 'tertunda') $base->whereNull('delivered_at')->whereNotNull('eta')->where('eta', '<', now());

        $shipments = $base->latest('updated_at')->paginate(15);

        $kpis = [
            'aktif'      => Shipment::whereIn('status', array_map(fn($e) => $e->value, \App\Enums\ShipmentStatus::inProgress()))->count(),
            'in_transit' => Shipment::where('status', \App\Enums\ShipmentStatus::inProgress())->count(),
            'pending'    => Shipment::whereNull('doc_verified_at')->count(),
            'late'       => Shipment::whereNull('delivered_at')->whereNotNull('eta')->where('eta', '<', now())->count(),
        ];

        $recentTracks = ShipmentTrack::with('shipment')->latest('tracked_at')->limit(10)->get();

        $shipments->getCollection()->transform(function ($s) {
            $s->progress_percent = $s->progress_percent ?? (int)($s->tracks_count ? min(100, $s->tracks_count * 20) : 0);
            return $s;
        });

        return compact('kpis', 'shipments', 'recentTracks');
    }
}
