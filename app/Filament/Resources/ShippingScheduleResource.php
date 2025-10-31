<?php

namespace App\Filament\Resources;

use App\Enums\ScheduleState;
use App\Filament\Resources\ShippingScheduleResource\Pages;
use App\Models\ShippingSchedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class ShippingScheduleResource extends Resource
{
    protected static ?string $model = ShippingSchedule::class;
    protected static ?string $navigationGroup = 'Operasional Kapal';
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Shipping Schedule TAM';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('voyage_id')->relationship('voyage', 'voyage_no')->searchable()->required()->label('Voyage'),
            Forms\Components\Section::make('Ringkas Voyage')->schema([
                Forms\Components\Placeholder::make('vessel')->content(fn($record) => optional(optional($record?->voyage)->vessel)->name ?: '-')->label('Kapal'),
                Forms\Components\Placeholder::make('line')->content(fn($record) => optional(optional(optional($record?->voyage)->vessel)->shippingLine)->name ?: '-')->label('Shipping Line'),
                Forms\Components\Placeholder::make('route')->content(fn($record) => optional($record?->voyage?->pol)->code . ' → ' . optional($record?->voyage?->pod)->code)->label('Rute'),
                Forms\Components\Placeholder::make('etd')->content(fn($record) => optional($record?->voyage?->etd)?->format('d M Y H:i') ?: '-')->label('ETD'),
                Forms\Components\Placeholder::make('eta')->content(fn($record) => optional($record?->voyage?->eta)?->format('d M Y H:i') ?: '-')->label('ETA'),
            ])->columns(5),
            Forms\Components\TextInput::make('cargo_plan')->numeric()->required()->default(0)->label('Cargo Plan'),
            Forms\Components\Select::make('state')->options(ScheduleState::options())->required()->label('Status'),
            Forms\Components\TextInput::make('approved_by_name')->label('Disetujui oleh'),
            Forms\Components\Textarea::make('final_note')->label('Catatan Final'),
            Forms\Components\TextInput::make('final_source')->label('Sumber'),
            Forms\Components\FileUpload::make('final_attachment_path')->label('Lampiran')->directory('schedule-attachments')->preserveFilenames(),
            Forms\Components\DateTimePicker::make('finalized_at')->label('Tanggal Final'),
        ]);
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table->columns([]);
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
