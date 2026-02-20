<?php

namespace App\Filament\Resources;

use App\Enums\VesselCheckStatus;
use App\Enums\VoyageDelayReason;
use App\Enums\VoyageOperationalStatus;
use App\Filament\Resources\VoyageResource\Pages;
use App\Models\Port;
use App\Models\Vessel;
use App\Models\Voyage;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Resources\Pages\EditRecord;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class VoyageResource extends Resource
{
    protected static ?string $model = Voyage::class;

    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationLabel = 'Voyage';
    protected static ?string $pluralLabel     = 'Voyage';
    protected static ?string $modelLabel      = 'Voyage';
    protected static ?int    $navigationSort  = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Voyage')
                ->schema([
                    Select::make('shipping_line_id')
                        ->label('Pelayaran')
                        ->relationship('shippingLine', 'name')
                        ->reactive()
                        ->afterStateUpdated(fn($state, callable $set) => $set('vessel_id', null))
                        ->required(),

                    Select::make('vessel_id')
                        ->label('Kapal')
                        ->required()
                        ->options(
                            fn(callable $get) =>
                            Vessel::where('shipping_line_id', $get('shipping_line_id'))
                                ->pluck('name', 'id')
                        ),

                    Hidden::make('vessel_plan_id')
                        ->default(request('vessel_plan_id')),

                    DatePicker::make('period_month')
                        ->label('Periode')
                        ->displayFormat('M Y')
                        ->native(false)
                        ->disabled()
                        ->dehydrated(),

                    Select::make('pol_id')
                        ->relationship('pol', 'code')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->label('POL'),

                    Select::make('pod_id')
                        ->relationship('pod', 'code')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->label('POD'),

                    TextInput::make('voyage_no')
                        ->label('No Voyage')
                        ->maxLength(50)
                        ->nullable()
                        ->required(fn($livewire) => $livewire instanceof EditRecord),
                ])
                ->columns(2),

            Section::make('Rencana Keberangkatan')
                ->schema([
                    DateTimePicker::make('etd')
                        ->label('ETD (Plan)')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $set('period_month', Carbon::parse($state)->startOfMonth()->toDateString());
                            }
                        }),

                    DateTimePicker::make('eta')
                        ->label('ETA (Plan)')
                        ->required(),

                    TextInput::make('cargo_plan')
                        ->label('Rencana Muatan (unit)')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->visible(fn($livewire) => ! ($livewire instanceof EditRecord)),
                ])
                ->columns(3),

            Section::make('Perubahan Jadwal')
                ->visible(fn($livewire) => $livewire instanceof EditRecord)
                ->schema([
                    Toggle::make('is_delayed')
                        ->label('Jadwal Berubah?')
                        ->reactive(),

                    Select::make('delay_reason')
                        ->label('Alasan Perubahan')
                        ->options(
                            collect(VoyageDelayReason::cases())
                                ->mapWithKeys(fn($c) => [$c->value => $c->label()])
                                ->toArray()
                        )
                        ->visible(fn($get) => $get('is_delayed'))
                        ->required(fn($get) => $get('is_delayed')),

                    DateTimePicker::make('etd')
                        ->label('ETD Revisi')
                        ->native(false)
                        ->visible(fn($get) => $get('is_delayed'))
                        ->required(fn($get) => $get('is_delayed')),

                    DateTimePicker::make('eta')
                        ->label('ETA Revisi')
                        ->native(false)
                        ->visible(fn($get) => $get('is_delayed'))
                        ->required(fn($get) => $get('is_delayed')),
                ])
                ->columns(2),

            Section::make('Actual (H-0)')
                ->visible(fn($livewire) => $livewire instanceof EditRecord)
                ->schema([
                    DateTimePicker::make('atd_at')->label('ATD'),
                    DateTimePicker::make('ata_at')->label('ATA'),

                    TextInput::make('actual_sailing_days')
                        ->label('Aktual Berlayar (hari)')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('cargo_actual')
                        ->label('Aktual Muatan')
                        ->numeric()
                        ->minValue(0),
                ])
                ->columns(3),

            Section::make('Catatan')
                ->schema([
                    Textarea::make('final_note')
                        ->rows(4),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('shippingLine.name')
                    ->label('Pelayaran')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('vessel.name')
                    ->label('Kapal')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('voyage_no')
                    ->label('Voyage')
                    ->sortable()
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('pol.code')->label('POL'),
                TextColumn::make('pod.code')->label('POD'),

                TextColumn::make('period_month')
                    ->label('Periode')
                    ->date('M Y'),

                TextColumn::make('etd')
                    ->label('ETD')
                    ->dateTime(),

                TextColumn::make('eta')
                    ->label('ETA')
                    ->dateTime(),

                TextColumn::make('operational_status_label')
                    ->label('Status')
                    ->badge()
                    ->color(fn($record) => $record->operational_status_color),

                TextColumn::make('is_delayed')
                    ->label('Delay')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'Ya' : 'Tidak')
                    ->color(fn($state) => $state ? 'danger' : 'gray'),

                TextColumn::make('delay_logs_count')
                    ->label('Revisi')
                    ->counts('delayLogs')
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'warning' : 'gray')
                    ->action(
                        Tables\Actions\Action::make('history')
                            ->modalHeading('Riwayat Perubahan Jadwal')
                            ->modalSubmitAction(false)
                            ->modalContent(
                                fn($record) =>
                                view('filament.voyage.delay-history', [
                                    'logs' => $record->delayLogs()->latest()->get(),
                                ])
                            )
                    ),
            ])
            ->defaultSort('etd', 'asc')
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $ratioExpr = "CASE 
        WHEN voyages.cargo_plan IS NULL OR voyages.cargo_plan = 0 
        THEN NULL 
        ELSE (voyages.cargo_actual * 1.0 / voyages.cargo_plan) 
        END";

        return parent::getEloquentQuery()
            ->select('voyages.*')
            ->selectRaw("COALESCE(($ratioExpr), 0) AS cargo_achievement_ratio")
            ->with(['shippingLine', 'pol', 'pod'])
            ->withCount('delayLogs');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListVoyages::route('/'),
            'create' => Pages\CreateVoyage::route('/create'),
            'edit'   => Pages\EditVoyage::route('/{record}/edit'),
        ];
    }
}
