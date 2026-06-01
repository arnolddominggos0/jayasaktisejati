<?php

namespace App\Filament\Customer\Resources;

use App\Enums\ShipmentStatus;
use App\Filament\Customer\Resources\ShipmentResource\Pages;
use App\Models\Shipment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Customer Shipment Resource
 * 
 * Read-only resource for customers to view their shipments.
 * Customers can only see shipments where they are the shipper (customer_id).
 */
class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Pengiriman Saya';

    protected static ?string $modelLabel = 'Pengiriman';

    protected static ?string $pluralModelLabel = 'Pengiriman';

    protected static ?string $navigationGroup = 'Pengiriman';

    protected static ?int $navigationSort = 1;

    /**
     * Filter query to only show customer's own shipments
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        $user = Auth::user();
        $customerId = $user?->customer_id;

        if ($customerId) {
            $query->where('customer_id', $customerId);
        } else {
            // If no customer_id, return empty result
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * Disable create action - customers cannot create shipments
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Disable edit action - customers cannot edit shipments
     */
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    /**
     * Disable delete action - customers cannot delete shipments
     */
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    /**
     * Form configuration - read only for customer
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pengiriman')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('No. Resi')
                            ->disabled()
                            ->copyable(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Table configuration for listing shipments
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('No. Resi')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('font-bold')
                    ->icon('heroicon-o-clipboard-document')
                    ->iconPosition('after'),

                Tables\Columns\TextColumn::make('receiver.name')
                    ->label('Penerima')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Tidak ada data'),

                Tables\Columns\TextColumn::make('destination_city')
                    ->label('Kota Tujuan')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('service_type')
                    ->label('Layanan')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'SeaFreight' => 'Sea Freight',
                        'LandTrucking' => 'Land Trucking',
                        'CarCarrier' => 'Car Carrier',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => ShipmentStatus::tryFrom($state)?->color() ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => ShipmentStatus::tryFrom($state)?->label() ?? $state),

                Tables\Columns\TextColumn::make('eta')
                    ->label('Estimasi Sampai')
                    ->dateTime('d M Y')
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(ShipmentStatus::class),

                Tables\Filters\Filter::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Dari'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail')
                    ->icon('heroicon-o-eye'),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Tidak ada pengiriman')
            ->emptyStateDescription('Anda belum memiliki pengiriman yang tercatat di sistem.')
            ->emptyStateIcon('heroicon-o-truck')
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Get pages for this resource
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShipments::route('/'),
            'view' => Pages\ViewShipment::route('/{record}'),
        ];
    }

    /**
     * Get navigation badge showing count
     */
    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();
        $customerId = $user?->customer_id;

        if (!$customerId) {
            return null;
        }

        return static::getModel()::where('customer_id', $customerId)
            ->whereNotIn('status', ShipmentStatus::completed())
            ->count() ?: null;
    }

    /**
     * Get navigation badge color
     */
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
