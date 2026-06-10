<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnitResource\Pages\ListUnits;
use App\Filament\Resources\UnitResource\Pages\ViewUnit;
use App\Filament\Resources\UnitResource\RelationManagers\UnitInspectionsRelationManager;
use App\Models\Unit;
use Filament\Resources\Resource;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;

/**
 * UnitResource — Read-only viewer untuk unit & inspection timeline.
 *
 * Tidak ada form Create/Edit pada resource ini.
 * Unit dibuat lewat ShipmentResource.
 * Inspection dibuat lewat UnitInspectionGenerator (Tinker / Command).
 */
class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static ?string $navigationIcon  = 'heroicon-m-cube';
    protected static ?string $navigationLabel = 'Unit';
    protected static ?string $navigationGroup = 'Shipment';
    protected static ?int    $navigationSort  = 30;

    protected static ?string $recordTitleAttribute = 'chassis_no';

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->width(60),

                TextColumn::make('model_no')
                    ->label('Model')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('chassis_no')
                    ->label('Chassis No')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('engine_no')
                    ->label('Engine No')
                    ->searchable()
                    ->toggleable()
                    ->fontFamily('mono'),

                TextColumn::make('color')
                    ->label('Warna')
                    ->toggleable(),

                TextColumn::make('shipment.code')
                    ->label('Shipment')
                    ->searchable()
                    ->url(fn (Unit $record) => $record->shipment_id
                        ? ShipmentResource::getUrl('view', ['record' => $record->shipment_id])
                        : null),

                TextColumn::make('shipment.voyage.voyage_no')
                    ->label('Voyage')
                    ->sortable(),

                TextColumn::make('inspections_count')
                    ->label('Inspeksi')
                    ->counts('inspections')
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'gray'),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('model_no')
                    ->label('Model')
                    ->options(fn () => Unit::query()
                        ->whereNotNull('model_no')
                        ->distinct()
                        ->pluck('model_no', 'model_no')
                        ->toArray()
                    ),
            ])
            ->searchable();
    }

    // ── Infolist (View page) ──────────────────────────────────────────────────

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Identitas Unit')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('model_no')
                            ->label('Model'),
                        TextEntry::make('chassis_no')
                            ->label('Chassis No')
                            ->copyable()
                            ->fontFamily('mono'),
                        TextEntry::make('engine_no')
                            ->label('Engine No')
                            ->copyable()
                            ->fontFamily('mono'),
                        TextEntry::make('color')
                            ->label('Warna'),
                        TextEntry::make('reg_no')
                            ->label('No. Polisi')
                            ->default('—'),
                        TextEntry::make('do_number')
                            ->label('DO Number')
                            ->default('—'),
                    ]),

                Section::make('Shipment')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('shipment.code')
                            ->label('Kode Shipment'),
                        TextEntry::make('shipment.route_from')
                            ->label('Asal'),
                        TextEntry::make('shipment.route_to')
                            ->label('Tujuan'),
                        TextEntry::make('shipment.voyage.voyage_no')
                            ->label('Voyage'),
                        TextEntry::make('shipment.status')
                            ->label('Status Shipment')
                            ->badge()
                            ->color(fn (string $state) => match ($state) {
                                'delivered' => 'success',
                                'transit'   => 'info',
                                'pickup'    => 'warning',
                                default     => 'gray',
                            }),
                    ]),

                Section::make('Catatan')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Catatan')
                            ->default('—'),
                    ])
                    ->collapsed(),
            ]);
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public static function getRelationManagers(): array
    {
        return [
            UnitInspectionsRelationManager::class,
        ];
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => ListUnits::route('/'),
            'view'  => ViewUnit::route('/{record}'),
        ];
    }

    // ── No Create / Edit ──────────────────────────────────────────────────────

    public static function canCreate(): bool
    {
        return false;
    }
}
