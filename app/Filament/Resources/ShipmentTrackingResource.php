<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShipmentTrackingResource\Pages;
use App\Models\ShipmentTrack;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;

class ShipmentTrackingResource extends Resource
{
    protected static ?string $model = ShipmentTrack::class;
    protected static ?string $navigationGroup = 'Pelacakan & Monitoring';
    protected static ?string $navigationLabel = 'Shipment Tracking';
    protected static ?string $navigationIcon = 'heroicon-m-map-pin';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Select::make('shipment_id')
                ->label('Shipment')
                ->relationship('shipment','code', modifyQueryUsing: fn($query)=>$query->orderByDesc('created_at'))
                ->searchable()->preload()->required(),

            Forms\Components\DateTimePicker::make('tracked_at')->label('Waktu')->required()->default(now()),
            Forms\Components\Select::make('status')
                ->options(collect(\App\Enums\ShipmentStatus::cases())->mapWithKeys(fn($c)=>[$c->value=>$c->label()]))
                ->native(false),
            Forms\Components\TextInput::make('checkpoint')->label('Checkpoint (event)')->maxLength(80),
            Forms\Components\TextInput::make('location')->label('Lokasi')->maxLength(120),
            Forms\Components\Textarea::make('note')->label('Catatan')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            TextColumn::make('shipment.code')->label('Kode')->badge()->searchable(),
            TextColumn::make('shipment.customer.name')->label('Customer')->toggleable(),
            TextColumn::make('tracked_at')->label('Waktu')->since()->sortable(),
            TextColumn::make('status')->label('Status')->badge(),
            TextColumn::make('checkpoint')->label('Checkpoint')->badge()->color('info'),
            TextColumn::make('location')->label('Lokasi')->limit(30),
            TextColumn::make('note')->label('Catatan')->limit(40),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')
                ->options(collect(\App\Enums\ShipmentStatus::cases())->mapWithKeys(fn($c)=>[$c->value=>$c->label()])),
            Tables\Filters\Filter::make('date_range')
                ->form([
                    Forms\Components\DatePicker::make('from')->label('Dari'),
                    Forms\Components\DatePicker::make('to')->label('Sampai'),
                ])
                ->query(function ($query, array $data) {
                    return $query
                        ->when($data['from'] ?? null, fn($qq,$d)=>$qq->whereDate('tracked_at','>=',$d))
                        ->when($data['to'] ?? null,   fn($qq,$d)=>$qq->whereDate('tracked_at','<=',$d));
                }),
        ])->actions([
            Tables\Actions\EditAction::make()->label('Ubah'),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make()->label('Hapus'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListShipmentTrackings::route('/'),
            'create' => Pages\CreateShipmentTracking::route('/create'),
            'edit'   => Pages\EditShipmentTracking::route('/{record}/edit'),
            'dashboard' => Pages\TrackingDashboard::route('/dashboard'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['shipment.customer']);
    }
}