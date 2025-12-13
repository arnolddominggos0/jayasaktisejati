<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoyageResource\Pages;
use App\Models\Port;
use App\Models\Voyage;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Resources\Pages\EditRecord;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class VoyageResource extends Resource
{
    protected static ?string $model = Voyage::class;

    protected static ?string $navigationGroup = 'Monitoring Kapal';
    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationLabel = 'Voyage';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Voyage')
                ->schema([
                    Select::make('vessel_id')
                        ->relationship('vessel', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->label('Kapal'),
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
                        ->label('Voyage No')
                        ->required()
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

            Section::make('Delay (Jika ETA mundur)')
                ->visible(fn($livewire) => $livewire instanceof EditRecord)
                ->schema([
                    Toggle::make('is_delayed')
                        ->label('Voyage Delay?')
                        ->reactive()
                        ->inline(false)
                        ->afterStateUpdated(function ($state, callable $set, $get) {
                            if ($state) {
                                $etd = $get('etd');
                                if ($etd) {
                                    $auto = Carbon::parse($etd)->addDay();
                                    $set('rescheduled_etd', $auto->toDateTimeString());
                                }
                                $eta = $get('eta');
                                if ($eta) {
                                    $autoEta = Carbon::parse($eta)->addDay();
                                    $set('rescheduled_eta', $autoEta->toDateTimeString());
                                }
                                $set('delay_reported_at', now()->toDateTimeString());
                            } else {
                                $set('rescheduled_etd', null);
                                $set('rescheduled_eta', null);
                            }
                        }),
                    Textarea::make('delay_reason')
                        ->label('Delay Reason')
                        ->rows(3)
                        ->visible(fn($get) => (bool) $get('is_delayed')),
                    DateTimePicker::make('rescheduled_etd')
                        ->label('ETD (Reschedule)')
                        ->required(fn($get) => (bool) $get('is_delayed'))
                        ->visible(fn($get) => (bool) $get('is_delayed'))
                        ->native(false)
                        ->minDate(fn($get) => $get('etd')),
                    DateTimePicker::make('rescheduled_eta')
                        ->label('ETA (Reschedule)')
                        ->required(fn($get) => (bool) $get('is_delayed'))
                        ->visible(fn($get) => (bool) $get('is_delayed'))
                        ->native(false)
                        ->minDate(fn($get) => $get('rescheduled_etd')),
                ])
                ->columns(2),

            Section::make('Finalisasi')
                ->visible(fn($livewire) => $livewire instanceof EditRecord)
                ->schema([
                    Toggle::make('is_final')
                        ->label('Tandai Final')
                        ->inline(false)
                        ->reactive()
                        ->visible(fn() => Auth::check() && (Auth::user()->hasRole('approver') || Auth::user()->hasRole('admin')))
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $set('finalized_at', now()->toDateTimeString());
                                if (Auth::check()) {
                                    $set('finalized_by', Auth::id());
                                    $set('finalized_by_name', Auth::user()->name);
                                }
                            } else {
                                $set('finalized_at', null);
                                $set('finalized_by', null);
                                $set('finalized_by_name', null);
                            }
                        }),
                    TextInput::make('finalized_by_name')
                        ->label('Finalized By')
                        ->disabled()
                        ->visible(fn($get) => (bool) $get('finalized_by')),
                    DateTimePicker::make('finalized_at')
                        ->label('Waktu Finalisasi')
                        ->native(false)
                        ->disabled()
                        ->visible(fn($get) => (bool) $get('finalized_at')),
                ])
                ->columns(2),

            Section::make('Actual (H-0)')
                ->visible(fn($livewire) => $livewire instanceof EditRecord)
                ->schema([
                    DateTimePicker::make('atd_at')
                        ->label('ATD (Actual Departure)')
                        ->native(false)
                        ->live()
                        ->nullable(),
                    DateTimePicker::make('ata_at')
                        ->label('ATA (Actual Arrival)')
                        ->native(false)
                        ->live()
                        ->nullable(),
                    TextInput::make('actual_sailing_days')
                        ->label('Actual Sailing (hari)')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('cargo_actual')
                        ->label('Actual Muatan (unit)')
                        ->numeric()
                        ->minValue(0),
                    DateTimePicker::make('cargo_actual_reported_at')
                        ->label('Waktu Laporan Actual')
                        ->native(false)
                        ->disabled(),
                    TextInput::make('cargo_actual_reported_by')
                        ->label('Dilaporkan Oleh')
                        ->maxLength(100)
                        ->disabled(),
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
                TextColumn::make('vessel.shippingLine.name')
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
                    ->searchable(),
                TextColumn::make('pol.code')->label('POL')->sortable(),
                TextColumn::make('pod.code')->label('POD')->sortable(),
                TextColumn::make('period_month')
                    ->label('Periode')
                    ->date('M Y')
                    ->sortable(),
                TextColumn::make('etd')->label('ETD')->dateTime()->sortable(),
                TextColumn::make('eta')->label('ETA')->dateTime()->sortable(),
                TextColumn::make('cargo_plan')->label('Plan TAM')->numeric()->sortable()->alignRight(),
                TextColumn::make('cargo_actual')->label('Actual TAM')->numeric()->alignRight()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cargo_achievement_percent')
                    ->label('Tercapai (%)')
                    ->getStateUsing(fn(Voyage $record) => $record->cargo_achievement_percent)
                    ->formatStateUsing(fn($state) => is_null($state) ? '-' : number_format((float)$state, 2) . '%')
                    ->sortable('cargo_achievement_ratio')
                    ->color(fn($state) => is_null($state) ? 'secondary' : ($state >= 100 ? 'success' : ($state >= 75 ? 'warning' : 'danger')))
                    ->alignRight(),
                TextColumn::make('status_label')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function (Voyage $record) {
                        if ($record->atd_at) return 'Sudah jalan';
                        if (! $record->etd) return 'Tidak diketahui';
                        return 'Belum jalan';
                    })
                    ->color(fn($state) => match ($state) {
                        'Sudah jalan' => 'success',
                        'Belum jalan' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('actual_sailing_days')
                    ->label('Sailing (hari)')
                    ->formatStateUsing(fn($state) => is_null($state) ? '-' : ((int)floor((float)$state) . ' hari ' . (int)round(((float)$state - floor((float)$state)) * 24) . ' jam'))
                    ->sortable()
                    ->toggleable()
                    ->alignRight(),
                BooleanColumn::make('is_delayed')
                    ->label('Delay')
                    ->trueIcon('heroicon-o-exclamation-circle')
                    ->falseIcon('heroicon-o-check')
                    ->trueColor('danger')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                BooleanColumn::make('is_final')
                    ->label('Final')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('finalized_by_name')->label('Finalized By')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('finalized_at')->label('Finalized At')->dateTime()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('finalized_at')->label('Finalized At')->dateTime()->sortable(),
                TextColumn::make('finalized_by_name')->label('Finalized By')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('period_month')
                    ->label('Periode')
                    ->options(function () {
                        return Voyage::query()
                            ->select('period_month')
                            ->distinct()
                            ->whereNotNull('period_month')
                            ->orderByDesc('period_month')
                            ->get()
                            ->mapWithKeys(fn($row) => [
                                $row->period_month->toDateString() => $row->period_month->format('M Y'),
                            ]);
                    })
                    ->default(now()->startOfMonth()->toDateString()),
                Tables\Filters\SelectFilter::make('status_voyage')
                    ->label('Status Voyage')
                    ->options([
                        'upcoming' => 'Belum jalan',
                        'past'     => 'Sudah jalan',
                    ])
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;
                        return match ($value) {
                            'upcoming' => $query->whereNull('atd_at'),
                            'past'     => $query->whereNotNull('atd_at'),
                            default    => $query,
                        };
                    }),
            ])
            ->defaultSort('etd', 'desc')
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
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
            ->with(['vessel.shippingLine', 'pol', 'pod']);
    }
}
