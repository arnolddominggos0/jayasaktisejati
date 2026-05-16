<?php

namespace App\Filament\Resources;

use App\Enums\VoyageDelayReason;
use App\Enums\VoyageOperationalStatus;
use App\Filament\Resources\VoyageResource\Pages;
use App\Models\Vessel;
use App\Models\Voyage;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Resources\Pages\EditRecord;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class VoyageResource extends Resource
{
    protected static ?string $model = Voyage::class;

    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationLabel = 'Data Voyage';
    protected static ?string $pluralLabel     = 'Voyage';
    protected static ?string $modelLabel      = 'Voyage';
    protected static ?int    $navigationSort  = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            // ── 1. Voyage Identity ───────────────────────────────
            Section::make('Identitas Voyage')
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

                    TextInput::make('voyage_no')
                        ->label('No Voyage')
                        ->maxLength(50)
                        ->nullable()
                        ->required(fn($livewire) => $livewire instanceof EditRecord),

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

                    DatePicker::make('period_month')
                        ->label('Periode')
                        ->displayFormat('M Y')
                        ->native(false)
                        ->disabled()
                        ->dehydrated(),
                ])
                ->columns(2),

            // ── 2. Planning Schedule ─────────────────────────────
            Section::make('Jadwal Perencanaan (Planning)')
                ->schema([
                    DateTimePicker::make('etb')
                        ->label('ETB (Estimasi Sandar)')
                        ->native(false),

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

                    Select::make('manual_delay_reason')
                        ->label('Alasan Perubahan Jadwal')
                        ->options(
                            collect(VoyageDelayReason::cases())
                                ->mapWithKeys(fn($c) => [$c->value => $c->label()])
                                ->toArray()
                        )
                        ->helperText('Isi jika terjadi perubahan ETD/ETA. Delay log akan tercatat otomatis.'),
                ])
                ->columns(2),

            // ── 3. Actual Operation ──────────────────────────────
            Section::make('Operasi Aktual (Actual)')
                ->visible(fn($livewire) => $livewire instanceof EditRecord)
                ->schema([
                    DateTimePicker::make('atb_at')
                        ->label('ATB (Aktual Sandar)')
                        ->native(false),

                    DateTimePicker::make('closing_at')
                        ->label('Closing Date')
                        ->native(false),

                    DateTimePicker::make('atd_at')
                        ->label('ATD (Aktual Berangkat)')
                        ->native(false),

                    DateTimePicker::make('ata_at')
                        ->label('ATA (Aktual Tiba)')
                        ->native(false),

                    TextInput::make('cargo_actual')
                        ->label('Aktual Muatan')
                        ->numeric()
                        ->minValue(0),
                ])
                ->columns(2),

            // ── 4. KPI & SLA (Read-only) ─────────────────────────
            Section::make('KPI & SLA')
                ->visible(fn($livewire) => $livewire instanceof EditRecord)
                ->schema([
                    Placeholder::make('otb_status')
                        ->label('OTB (On Time Berthing)')
                        ->content(fn($record) => $record->otb_status?->label() ?? '—'),

                    Placeholder::make('otd_status')
                        ->label('OTD (On Time Departure)')
                        ->content(fn($record) => $record->otd_status?->label() ?? '—'),

                    Placeholder::make('ota_status')
                        ->label('OTA (On Time Arrival)')
                        ->content(fn($record) => $record->ota_status?->label() ?? '—'),

                    Placeholder::make('sla_status')
                        ->label('SLA Pelayaran')
                        ->content(fn($record) => $record->sla_status?->label() ?? '—'),

                    Placeholder::make('planned_sailing_days')
                        ->label('Rencana Berlayar')
                        ->content(fn($record) => $record->planned_sailing_days ? $record->planned_sailing_days . ' hari' : '—'),

                    Placeholder::make('actual_sailing_days')
                        ->label('Aktual Berlayar')
                        ->content(fn($record) => $record->actual_sailing_days ? $record->actual_sailing_days . ' hari' : '—'),

                    Placeholder::make('departure_delay_days')
                        ->label('Keterlambatan Berangkat')
                        ->content(fn($record) => $record->departure_delay_days ? $record->departure_delay_days . ' hari' : 'Tepat Waktu'),

                    Placeholder::make('delay_root_cause_label')
                        ->label('Root Cause Delay')
                        ->content(fn($record) => $record->delay_root_cause_label ?? '—'),
                ])
                ->columns(3),

            // ── 5. Audit & Notes ─────────────────────────────────
            Section::make('Audit & Catatan')
                ->schema([
                    Textarea::make('final_note')
                        ->label('Catatan Akhir')
                        ->rows(4),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('vessel.name')
                    ->sortable()
                    ->searchable()
                    ->label('Kapal'),

                TextColumn::make('voyage_no')
                    ->searchable()
                    ->label('No Voyage'),

                TextColumn::make('route')
                    ->label('Rute')
                    ->formatStateUsing(fn($record) => ($record->pol?->code ?? '-') . ' → ' . ($record->pod?->code ?? '-')),

                TextColumn::make('period_month')
                    ->date('M Y')
                    ->label('Periode'),

                TextColumn::make('etd')
                    ->dateTime('d M Y H:i')
                    ->label('ETD (Plan)'),

                TextColumn::make('eta')
                    ->dateTime('d M Y H:i')
                    ->label('ETA (Plan)'),

                TextColumn::make('atd_at')
                    ->dateTime('d M Y H:i')
                    ->label('ATD (Actual)')
                    ->placeholder('—'),

                TextColumn::make('ata_at')
                    ->dateTime('d M Y H:i')
                    ->label('ATA (Actual)')
                    ->placeholder('—'),

                TextColumn::make('operational_status_enum')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state->label())
                    ->color(fn($state) => match ($state) {
                        VoyageOperationalStatus::DELAYED => 'danger',
                        VoyageOperationalStatus::SAILING => 'info',
                        VoyageOperationalStatus::SCHEDULED => 'gray',
                        VoyageOperationalStatus::COMPLETED => 'success',
                    })
                    ->label('Status'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['shippingLine', 'vessel', 'pol', 'pod'])
            ->withCount('delayLogs');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListVoyages::route('/'),
            'create' => Pages\CreateVoyage::route('/create'),
            'view'   => Pages\ViewVoyage::route('/{record}'),
            'edit'   => Pages\EditVoyage::route('/{record}/edit'),
        ];
    }
}
