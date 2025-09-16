<?php

namespace App\Filament\Pages;

use App\Models\Shipment;
use Filament\Pages\Page;

class TrackingDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static string $view = 'filament.pages.tracking-dashboard';
    protected static ?string $navigationLabel = 'Pelacakan';
    protected static ?string $navigationGroup = 'Tracking & Monitoring';

    public $shipments;

    public function mount()
    {
        $this->shipments = Shipment::with('customer', 'latestTrack')
            ->latest()
            ->paginate(10);
    }
}
