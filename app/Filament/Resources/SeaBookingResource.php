<?php

namespace App\Filament\Resources;

use App\Enums\SeaBookingStatus;
use App\Filament\Resources\SeaBookingResource\Pages;
use App\Models\SeaBooking;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;

class SeaBookingResource extends Resource
{
    protected static ?string $model = SeaBooking::class;
    protected static ?string $navigationGroup = 'Booking Kontainer (Laut    )';
    protected static ?string $navigationIcon = 'heroicon-m-clipboard-document';
    protected static ?string $modelLabel = 'Sea Booking';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')->label('Kode Booking')->required()->unique(ignoreRecord: true),
            Forms\Components\Select::make('shipping_line_id')->relationship('shippingLine','name')->label('Shipping Line')->required(),
            Forms\Components\Select::make('voyage_id')->relationship('voyage','voyage_no')->label('Voyage')->searchable(),
            Forms\Components\Select::make('depot_id')->relationship('depot','name')->label('Depot'),
            Forms\Components\TextInput::make('ro_no')->label('RO No'),
            Forms\Components\TextInput::make('rc_no')->label('RC No'),
            Forms\Components\TextInput::make('si_no')->label('SI No'),
            Forms\Components\Select::make('status')->label('Status')
                ->options(collect(SeaBookingStatus::cases())->mapWithKeys(fn($c)=>[$c->value=>$c->label()]))->required(),
            Forms\Components\Textarea::make('notes')->label('Catatan')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('code')->badge()->label('Kode'),
            Tables\Columns\TextColumn::make('shippingLine.name')->label('Line'),
            Tables\Columns\TextColumn::make('voyage.vessel.name')->label('Vessel'),
            Tables\Columns\TextColumn::make('voyage.voyage_no')->label('Voyage'),
            Tables\Columns\TextColumn::make('voyage.portFrom.code')->label('POL')->badge(),
            Tables\Columns\TextColumn::make('voyage.portTo.code')->label('POD')->badge(),
            Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                ->state(fn($record) => $record->status?->label() ?? (string) $record->status)
                ->color(fn($record) => $record->status?->color() ?? 'gray'),
            Tables\Columns\TextColumn::make('updated_at')->since()->label('Diubah'),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')->options(collect(SeaBookingStatus::cases())->mapWithKeys(fn($c)=>[$c->value=>$c->label()])),
            Tables\Filters\SelectFilter::make('shipping_line_id')->label('Shipping Line')->relationship('shippingLine','name'),
        ])->actions([
            Tables\Actions\EditAction::make()->label('Ubah'),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make()->label('Hapus'),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            // \App\Filament\Resources\SeaBookingResource\RelationManagers\ContainersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSeaBookings::route('/'),
            'create' => Pages\CreateSeaBooking::route('/create'),
            'edit'   => Pages\EditSeaBooking::route('/{record}/edit'),
        ];
    }
}
