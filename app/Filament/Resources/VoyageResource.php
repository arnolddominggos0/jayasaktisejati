<?php

namespace App\Filament\Resources;

use App\Enums\VoyageDelayReason;
use App\Filament\Resources\VoyageResource\Pages;
use App\Models\Port;
use App\Models\ShippingLine;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class VoyageResource extends Resource
{
    protected static ?string $model = Voyage::class;

    protected static ?string $navigationGroup = 'Monitoring Kapal TAM';
    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationLabel = 'Voyage';

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
                        ->default(request('period_month'))
                        ->disabled()
                        ->dehydrated(),

                    Select::make('pol_id')
                        ->relationship('pol', 'code')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->getSearchResultsUsing(function (string $search) {
                            return Port::query()
                                ->when($search !== '', function ($q) use ($search) {
                                    $q->where('code', 'ilike', "%{$search}%")
                                        ->orWhere('name', 'ilike', "%{$search}%");
                                })
                                ->limit(50)
                                ->pluck('code', 'id');
                        })
                        ->getOptionLabelUsing(function ($value) {
                            $port = Port::find($value);
                            return $port ? "{$port->code} — {$port->name}" : null;
                        })
                        ->label('POL'),
                    Select::make('pod_id')
                        ->relationship('pod', 'code')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->getSearchResultsUsing(function (string $search) {
                            return Port::query()
                                ->when($search !== '', function ($q) use ($search) {
                                    $q->where('code', 'ilike', "%{$search}%")
                                        ->orWhere('name', 'ilike', "%{$search}%");
                                })
                                ->limit(50)
                                ->pluck('code', 'id');
                        })
                        ->getOptionLabelUsing(function ($value) {
                            $port = Port::find($value);
                            return $port ? "{$port->code} — {$port->name}" : null;
                        })
                        ->label('POD'),
                    TextInput::make('voyage_no')
                        ->label('No Voyage')
                        ->nullable()
                        ->helperText('Boleh dikosongkan, bisa diisi saat jadwal sudah pasti')
                        ->required(fn($livewire) => $livewire instanceof EditRecord)
                        ->maxLength(50),
                ])
                ->columns(2),

            Section::make('Rencana Keberangkatan')
                ->schema([
                    DateTimePicker::make('etd')
                        ->required()
                        ->live()
                        ->label('ETD (Plan)')
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $set('period_month', Carbon::parse($state)->startOfMonth()->toDateString());
                            }
                        }),
                    DateTimePicker::make('eta')
                        ->required()
                        ->label('ETA (Plan)'),
                    DatePicker::make('period_month')
                        ->label('Periode')
                        ->displayFormat('M Y')
                        ->native(false)
                        ->disabled()
                        ->dehydrated(),
                    TextInput::make('cargo_plan')
                        ->label('Rencana Muatan (unit)')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->visible(fn($livewire) => !($livewire instanceof EditRecord)),
                ])
                ->columns(3),

            Section::make('Perubahan Jadwal (Jika ETA/ETD direvisi)')
                ->visible(fn($livewire) => $livewire instanceof EditRecord)
                ->schema([

                    Toggle::make('is_delayed')
                        ->label('Jadwal Berubah?')
                        ->reactive()
                        ->inline(false)
                        ->afterStateHydrated(function (Toggle $component, $state) {
                            $component->state((bool) $state);
                        }),

                    Select::make('delay_reason')
                        ->label('Alasan Perubahan Jadwal')
                        ->options(
                            collect(VoyageDelayReason::cases())
                                ->mapWithKeys(fn($c) => [$c->value => $c->label()])
                                ->toArray()
                        )
                        ->required(fn($get) => (bool) $get('is_delayed'))
                        ->visible(fn($get) => (bool) $get('is_delayed'))
                        ->afterStateHydrated(function (Select $component, $state) {
                            $component->state($state);
                        }),

                    DateTimePicker::make('rescheduled_etd')
                        ->label('ETD Revisi')
                        ->native(false)
                        ->required(fn($get) => (bool) $get('is_delayed'))
                        ->visible(fn($get) => (bool) $get('is_delayed'))
                        ->afterStateHydrated(function (DateTimePicker $component, $state, $record) {
                            if (! $state && $record?->etd) {
                                $component->state(
                                    Carbon::parse($record->etd)->addDay()
                                );
                            }
                        }),

                    DateTimePicker::make('rescheduled_eta')
                        ->label('ETA Revisi')
                        ->native(false)
                        ->required(fn($get) => (bool) $get('is_delayed'))
                        ->visible(fn($get) => (bool) $get('is_delayed'))
                        ->afterStateHydrated(function (DateTimePicker $component, $state, $record) {
                            if (! $state && $record?->eta) {
                                $component->state(
                                    Carbon::parse($record->eta)->addDay()
                                );
                            }
                        }),

                ])
                ->columns(2),

            Section::make('Aktual (H-0)')
                ->visible(fn($livewire) => $livewire instanceof EditRecord)
                ->schema([

                    DateTimePicker::make('atd_at')
                        ->label('ATD (Aktual Keberangkatan)')
                        ->native(false)
                        ->live()
                        ->nullable()
                        ->afterStateHydrated(fn($component, $state) => $component->state($state)),

                    DateTimePicker::make('ata_at')
                        ->label('ATA (Aktual Kedatangan)')
                        ->native(false)
                        ->live()
                        ->nullable()
                        ->afterStateHydrated(fn($component, $state) => $component->state($state)),

                    TextInput::make('actual_sailing_days')
                        ->label('Aktual Berlayar (hari)')
                        ->disabled()
                        ->dehydrated(false)
                        ->afterStateHydrated(function ($component, $state, $record) {
                            $component->state($record?->actual_sailing_days);
                        }),

                    TextInput::make('cargo_actual')
                        ->label('Aktual Muatan (unit)')
                        ->numeric()
                        ->minValue(0)
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state !== null) {
                                $set('cargo_actual_reported_at', now());
                                $set('cargo_actual_reported_by', auth()->user()?->name);
                            }
                        }),

                    DateTimePicker::make('cargo_actual_reported_at')
                        ->label('Waktu Laporan Aktual')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('cargo_actual_reported_by')
                        ->label('Dilaporkan Oleh')
                        ->disabled()
                        ->dehydrated(false),
                ])
                ->columns(3),


            Section::make('Catatan Tambahan')
                ->schema([
                    Textarea::make('final_note')
                        ->label('Catatan')
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

                TextColumn::make('pol.code')->label('POL')->sortable(),
                TextColumn::make('pod.code')->label('POD')->sortable(),

                TextColumn::make('period_month')
                    ->label('Periode')
                    ->date('M Y')
                    ->sortable(),

                TextColumn::make('etd')->label('ETD')->dateTime()->sortable(),
                TextColumn::make('eta')->label('ETA')->dateTime()->sortable(),

                TextColumn::make('status_operasional')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function (Voyage $record) {
                        if ($record->atd_at) return 'Sudah jalan';
                        if ($record->etd && $record->etd->isPast()) return 'Terlambat';
                        return 'Belum jalan';
                    })
                    ->color(fn($state) => match ($state) {
                        'Sudah jalan' => 'success',
                        'Terlambat'   => 'danger',
                        default       => 'warning',
                    }),
            ])
            ->filters([
                SelectFilter::make('period_month')
                    ->label('Periode')
                    ->options(
                        Voyage::query()
                            ->select('period_month')
                            ->distinct()
                            ->whereNotNull('period_month')
                            ->orderByDesc('period_month')
                            ->get()
                            ->mapWithKeys(fn($row) => [
                                $row->period_month->toDateString()
                                => $row->period_month->format('M Y'),
                            ])
                    ),
            ])
            ->defaultSort('etd', 'asc')
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListVoyages::route('/'),
            'create' => Pages\CreateVoyage::route('/create'),
            'edit'   => Pages\EditVoyage::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $ratioExpr = "CASE WHEN voyages.cargo_plan IS NULL OR voyages.cargo_plan = 0 THEN NULL ELSE (voyages.cargo_actual * 1.0 / voyages.cargo_plan) END";

        return parent::getEloquentQuery()
            ->select('voyages.*')
            ->selectRaw("COALESCE(($ratioExpr), 0) AS cargo_achievement_ratio")
            ->with(['shippingLine', 'pol', 'pod']);
    }
}
