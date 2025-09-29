<?php

namespace App\Filament\FC\Widgets;

use App\Models\ShipmentTrack;
use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class FcRecentActivities extends BaseWidget
{
    protected static ?string $heading = 'Aktivitas Tracking Terbaru';
    protected static ?string $pollingInterval = '60s';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $u = Filament::auth()->user();
        $fcCol = (string) config('fc.shipment_fc_column', 'coordinator_id');

        return $table
            ->query(function (): Builder {
                $u = Filament::auth()->user();
                $fcCol = (string) config('fc.shipment_fc_column', 'coordinator_id');

                $q = ShipmentTrack::query()
                    ->with([
                        'shipment:id,code,origin_city_id,destination_city_id,' . $fcCol,
                        'shipment.originCity:id,name',
                        'shipment.destinationCity:id,name',
                    ])
                    ->latest('tracked_at');

                if ($u?->branch_id) {
                    $q->whereHas('shipment', fn($s) => $s->where(function ($w) use ($u) {
                        $w->where('branch_id', $u->branch_id)->orWhereNull('branch_id');
                    }));
                }

                if ($u?->office_id) {
                    $q->whereHas('shipment', fn($s) => $s->where(function ($w) use ($u) {
                        $w->where('origin_office_id', $u->office_id)
                            ->orWhere('destination_office_id', $u->office_id)
                            ->orWhereNull('origin_office_id');
                    }));
                }

                $q->whereHas('shipment', fn($s) => $s->where($fcCol, Filament::auth()->id()));

                return $q;
            })
            ->columns([
                Tables\Columns\TextColumn::make('tracked_at')
                    ->label('Waktu')
                    ->since()
                    ->sortable(),

                Tables\Columns\TextColumn::make('shipment.code')
                    ->label('Kode')
                    ->badge()
                    ->extraAttributes(['class' => 'font-mono'])
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'info'    => ['pickup', 'loading', 'on_transit'],
                        'success' => ['delivered'],
                        'warning' => ['on_hold'],
                        'danger'  => ['problem', 'cancelled'],
                        'gray'    => ['waiting', 'draft'],
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('location_text')
                    ->label('Lokasi')
                    ->getStateUsing(function ($r) {
                        $o = $r->shipment->originCity->name ?? null;
                        $d = $r->shipment->destinationCity->name ?? null;
                        return $r->location_name ?: ($o && $d ? "{$o} → {$d}" : '—');
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('remark')
                    ->label('Catatan')
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50]);
    }
}
