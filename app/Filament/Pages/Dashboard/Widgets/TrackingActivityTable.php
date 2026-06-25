<?php

namespace App\Filament\Pages\Dashboard\Widgets;

use App\Filament\Resources\ShipmentTrackingResource;
use App\Models\ShipmentTrack;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class TrackingActivityTable extends BaseWidget
{
    protected static ?string $heading = 'Aktivitas Tracking Terbaru';
    protected static ?string $pollingInterval = '60s';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                $q = ShipmentTrack::query()
                    ->with([
                        'shipment:id,code,origin_city_id,destination_city_id',
                        'shipment.originCity:id,name',
                        'shipment.destinationCity:id,name',
                    ])
                    ->latest('tracked_at');

                $user = Filament::auth()?->user();
                if ($user && ! $user->isSuperAdmin()) {
                    if (Schema::hasColumn('shipments', 'branch_id') && $user->effectiveBranchId()) {
                        $q->whereHas('shipment', fn (Builder $s) => $s->where(fn ($w) => $w->where('branch_id', $user->effectiveBranchId())->orWhereNull('branch_id')));
                    } elseif (Schema::hasColumn('shipments', 'depot_id') && data_get($user, 'depot_id')) {
                        $q->whereHas('shipment', fn (Builder $s) => $s->where('depot_id', $user->depot_id));
                    }
                }

                return $q;
            })
            ->defaultSort('tracked_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('shipment.code')
                    ->label('Kode')
                    ->copyable()
                    ->url(fn (ShipmentTrack $r) =>
                        $r->shipment_id
                            ? ShipmentTrackingResource::getUrl('manage', ['record' => $r->shipment_id])
                            : null
                    )
                    ->openUrlInNewTab()
                    ->searchable()
                    ->sortable()
                    ->extraAttributes(['class' => 'whitespace-nowrap w-32 font-mono']),

                Tables\Columns\TextColumn::make('shipment.route_label')
                    ->label('Route')
                    ->state(fn (ShipmentTrack $r) => $r->shipment?->route_label ?? '—')
                    ->extraAttributes(['class' => 'max-w-[16rem] truncate']),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) =>
                        $state instanceof BackedEnum
                            ? (method_exists($state, 'label') ? $state->label() : $state->value)
                            : (string) $state
                    )
                    ->badge()
                    ->colors([
                        'warning' => 'pickup',
                        'info'    => 'transit',
                        'success' => 'delivered',
                        'danger'  => 'hold',
                        'gray'    => ['pending', 'created'],
                    ])
                    ->extraAttributes(['class' => 'whitespace-nowrap']),

                Tables\Columns\TextColumn::make('tracked_at')
                    ->label('Waktu')
                    ->since()
                    ->tooltip(fn ($state) =>
                        $state ? Carbon::parse($state)->locale('id')->translatedFormat('l, d F Y, H:i') : null
                    )
                    ->sortable()
                    ->extraAttributes(['class' => 'whitespace-nowrap w-44']),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->striped()
            ->searchOnBlur()
            ->headerActions([
                Tables\Actions\Action::make('lihatSemua')
                    ->label('Lihat semua')
                    ->icon('heroicon-m-arrow-right')
                    ->url(ShipmentTrackingResource::getUrl('index'))
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('Belum ada tracking')
            ->emptyStateDescription('Update terbaru akan muncul di sini begitu ada perubahan.');
    }
}
