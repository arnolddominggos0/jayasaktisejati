<?php

namespace App\Filament\Resources;

use App\Enums\ScheduleState;
use App\Filament\Resources\ShippingScheduleResource\Pages;
use App\Models\ShippingSchedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ShippingScheduleResource extends Resource
{
    protected static ?string $model = ShippingSchedule::class;
    protected static ?string $navigationGroup = 'Operasional Kapal';
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Jadwal Pengiriman TAM';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('voyage_id')->relationship('voyage', 'voyage_no')->required()->searchable()->label('Voyage'),
            Forms\Components\TextInput::make('cargo_plan')->numeric()->required()->minValue(1)->label('Cargo Plan'),
            Forms\Components\TextInput::make('jss')->maxLength(100)->label('JSS'),
            Forms\Components\TextInput::make('dwelling_days')->numeric()->label('Dwelling (hari)'),
            Forms\Components\Select::make('state')->options(ScheduleState::options())->required()->label('Status'),
            Forms\Components\Textarea::make('final_note')->label('Catatan'),
            Forms\Components\FileUpload::make('final_attachment_path')->label('Lampiran')->directory('schedule-attachments')->preserveFilenames(),
            Forms\Components\DateTimePicker::make('finalized_at')->label('Tanggal Final'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('voyage.vessel.shippingLine.name')->label('Shipping Line'),
            Tables\Columns\TextColumn::make('voyage.vessel.name')->label('Vessel'),
            Tables\Columns\TextColumn::make('voyage.vessel.capacity')->label('Vessel Capacity'),
            Tables\Columns\TextColumn::make('voyage.voyage_no')->label('Voyage No'),
            Tables\Columns\TextColumn::make('jss')->label('JSS'),
            Tables\Columns\TextColumn::make('cargo_plan')->label('Cargo Plan'), 
            Tables\Columns\TextColumn::make('dwelling_days')->label('Dwelling'),
            Tables\Columns\TextColumn::make('etd')->label('ETD')->dateTime(),
            Tables\Columns\TextColumn::make('eta')->label('ETA')->dateTime(),
            Tables\Columns\BadgeColumn::make('state')->label('Status')
                ->colors([
                    'warning' => fn($state) => ($state?->value ?? $state) === 'draft',
                    'info'    => fn($state) => ($state?->value ?? $state) === 'feedback',
                    'success' => fn($state) => ($state?->value ?? $state) === 'final',
                ])
                ->formatStateUsing(fn($state) => is_string($state) ? strtoupper($state) : strtoupper($state?->value)),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShippingSchedules::route('/'),
            'create' => Pages\CreateShippingSchedule::route('/create'),
            'edit' => Pages\EditShippingSchedule::route('/{record}/edit'),
        ];
    }
}
