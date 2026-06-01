<?php

namespace App\Filament\Customer\Widgets;

use App\Enums\ShipmentStatus;
use App\Filament\Customer\Resources\ShipmentResource;
use App\Models\Shipment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

/**
 * Recent Shipments Widget
 * 
 * Display recent shipments for customer in a table
 */
class RecentShipments extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Pengiriman Terbaru';

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $customerId = $user?->customer_id;

        return $table
            ->query(
                Shipment::query()
                    ->where('customer_id', $customerId)
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('No. Resi')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('font-bold'),

                Tables\Columns\TextColumn::make('receiver.name')
                    ->label('Penerima')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Tidak ada data'),

                Tables\Columns\TextColumn::make('destination_city')
                    ->label('Tujuan')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => ShipmentStatus::tryFrom($state)?->color() ?? 'gray'),

                Tables\Columns\TextColumn::make('eta')
                    ->label('Estimasi Sampai')
                    ->dateTime('d M Y')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Kirim')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Shipment $record): string => ShipmentResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading('Belum ada pengiriman')
            ->emptyStateDescription('Pengiriman Anda akan muncul di sini.')
            ->paginated(false);
    }
}
