<?php

namespace App\Filament\Resources;

use App\Enums\VoyageDelayReason;
use App\Enums\VoyageRegistryStatus;
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
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class VoyageResource extends Resource
{
    protected static ?string $model = Voyage::class;

    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Voyage Registry';
    protected static ?string $pluralLabel     = 'Voyage Registry';
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

            // ── 4. Registry Lifecycle ────────────────────────────
            Section::make('Status & Lifecycle')
                ->schema([
                    Select::make('registry_status')
                        ->label('Status Registrasi')
                        ->options(
                            collect(VoyageRegistryStatus::cases())
                                ->mapWithKeys(fn($c) => [$c->value => $c->label()])
                                ->toArray()
                        )
                        ->default(VoyageRegistryStatus::DRAFT->value)
                        ->required()
                        ->live(),

                    DateTimePicker::make('archived_at')
                        ->label('Tanggal Arsip')
                        ->native(false)
                        ->visible(fn(callable $get) => $get('registry_status') === VoyageRegistryStatus::ARCHIVED->value),
                ])
                ->columns(2),

            // ── 5. KPI & SLA (Read-only) ─────────────────────────
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

            // ── 6. Audit & Notes ─────────────────────────────────
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
            ->striped()
            ->defaultPaginationPageOption(25)
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->poll(null)
            ->columns([
                // ── Primary Identity Column ──
                Stack::make([
                    TextColumn::make('voyage_identity')
                        ->label('Voyage')
                        ->state(fn($record) => $record->voyage_no)
                        ->searchable(query: function (Builder $query, string $search): Builder {
                            return $query->where(function ($q) use ($search) {
                                $q->where('voyage_no', 'like', "%{$search}%")
                                    ->orWhereHas('vessel', function ($v) use ($search) {
                                        $v->where('name', 'like', "%{$search}%");
                                    });
                            });
                        })
                        ->weight('font-semibold')
                        ->color('gray'),

                    TextColumn::make('vessel_display')
                        ->label('')
                        ->state(fn($record) => $record->vessel?->name)
                        ->color('gray'),

                    TextColumn::make('route')
                        ->label('')
                        ->state(
                            fn($record) => ($record->pol?->code ?? '-') . ' → ' . ($record->pod?->code ?? '-')
                        )
                        ->color('gray'),

                    TextColumn::make('period_month')
                        ->label('')
                        ->date('M Y')
                        ->color('gray'),
                ])
                    ->space(1)
                    ->extraAttributes([
                        'class' => 'leading-tight min-w-[220px]',
                    ]),

                // ── Schedule Column ──
                Stack::make([
                    TextColumn::make('etd')
                        ->label('')
                        ->formatStateUsing(fn($state) => 'ETD ' . ($state ? $state->format('d M H:i') : '—'))
                        ->extraAttributes(['class' => 'text-[11px] text-gray-500']),
                    TextColumn::make('eta')
                        ->label('')
                        ->formatStateUsing(fn($state) => 'ETA ' . ($state ? $state->format('d M H:i') : '—'))
                        ->extraAttributes(['class' => 'text-[11px] text-gray-500']),
                ])
                    ->space(1)
                    ->extraAttributes(['class' => 'leading-tight']),

                // ── Status Column ──
                TextColumn::make('registry_status')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state?->label() ?? '—')
                    ->color(fn($state) => match ($state) {
                        VoyageRegistryStatus::DRAFT     => 'gray',
                        VoyageRegistryStatus::PLANNED   => 'gray',
                        VoyageRegistryStatus::ACTIVE    => 'primary',
                        VoyageRegistryStatus::DELAYED   => 'warning',
                        VoyageRegistryStatus::COMPLETED => 'success',
                        VoyageRegistryStatus::CLOSED    => 'gray',
                        VoyageRegistryStatus::ARCHIVED  => 'gray',
                        default => 'gray',
                    })
                    ->label('Status')
                    ->sortable()
                    ->alignment('center')
                    ->extraAttributes(['class' => 'text-[11px]']),

                // ── Updated Column ──
                Stack::make([
                    TextColumn::make('updated_at_date')
                        ->label('')
                        ->state(fn($record) => $record->updated_at?->format('d M Y'))
                        ->extraAttributes(['class' => 'text-[11px] text-gray-500']),
                    TextColumn::make('updated_at_time')
                        ->label('')
                        ->state(fn($record) => $record->updated_at?->format('H:i'))
                        ->extraAttributes(['class' => 'text-[11px] text-gray-400']),
                ])
                    ->space(1)
                    ->extraAttributes(['class' => 'leading-tight']),
            ])
            ->filters([
                SelectFilter::make('period_month')
                    ->label('Period')
                    ->options(
                        fn() => Voyage::query()
                            ->whereNotNull('period_month')
                            ->orderByDesc('period_month')
                            ->pluck('period_month')
                            ->unique()
                            ->mapWithKeys(function ($period) {

                                $date = $period instanceof Carbon
                                    ? $period
                                    : Carbon::parse($period);

                                return [
                                    $date->format('Y-m-01') => $date->format('M Y'),
                                ];
                            })
                            ->toArray()
                    )
                    ->native(false),

                SelectFilter::make('vessel_id')
                    ->label('Vessel')
                    ->relationship('vessel', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('registry_status')
                    ->label('Status')
                    ->options(
                        collect(VoyageRegistryStatus::cases())
                            ->mapWithKeys(fn($c) => [$c->value => $c->label()])
                            ->toArray()
                    )
                    ->native(false),

                TernaryFilter::make('include_archived')
                    ->label('Show Archived')
                    ->placeholder('Hide Archived')
                    ->trueLabel('Show Archived')
                    ->falseLabel('Hide Archived')
                    ->queries(
                        true: fn(Builder $query) => $query,
                        false: fn(Builder $query) => $query->where('registry_status', '!=', VoyageRegistryStatus::ARCHIVED->value),
                    )
                    ->default(false)
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->tooltip('Operational Sheet')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->size('xs'),

                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Edit Planning')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->size('xs'),

                Tables\Actions\Action::make('close')
                    ->label('')
                    ->tooltip('Close Voyage')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->size('xs')
                    ->requiresConfirmation()
                    ->modalHeading('Tutup Voyage')
                    ->modalDescription('Tindakan ini akan menutup voyage secara administratif. Lanjutkan?')
                    ->action(function (Voyage $record) {
                        $record->transitionRegistryStatus(VoyageRegistryStatus::CLOSED);
                        $record->save();
                    })
                    ->visible(fn(Voyage $record) => $record->registry_status
                        && ! $record->registry_status->isTerminal()),

                Tables\Actions\Action::make('reopen')
                    ->label('')
                    ->tooltip('Reopen Voyage')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->size('xs')
                    ->requiresConfirmation()
                    ->modalHeading('Buka Kembali Voyage')
                    ->modalDescription('Voyage akan dibuka kembali dari status Closed. Lanjutkan?')
                    ->action(function (Voyage $record) {
                        $record->registry_status = VoyageRegistryStatus::PLANNED;
                        $record->closing_at = null;
                        $record->save();
                    })
                    ->visible(fn(Voyage $record) => $record->registry_status === VoyageRegistryStatus::CLOSED),

                Tables\Actions\Action::make('archive')
                    ->label('')
                    ->tooltip('Archive Voyage')
                    ->icon('heroicon-o-archive-box')
                    ->color('gray')
                    ->size('xs')
                    ->requiresConfirmation()
                    ->modalHeading('Arsipkan Voyage')
                    ->modalDescription('Voyage akan dipindahkan ke arsip. Lanjutkan?')
                    ->action(function (Voyage $record) {
                        $record->transitionRegistryStatus(VoyageRegistryStatus::ARCHIVED);
                        $record->save();
                    })
                    ->visible(fn(Voyage $record) => $record->registry_status !== VoyageRegistryStatus::ARCHIVED),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('archive')
                    ->label('Archive Selected')
                    ->icon('heroicon-o-archive-box')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->transitionRegistryStatus(VoyageRegistryStatus::ARCHIVED);
                            $record->save();
                        }
                    }),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['shippingLine', 'vessel', 'pol', 'pod'])
            ->orderByRaw("
            CASE registry_status
                WHEN 'active' THEN 1
                WHEN 'delayed' THEN 2
                WHEN 'planned' THEN 3
                WHEN 'completed' THEN 4
                WHEN 'closed' THEN 5
                WHEN 'draft' THEN 6
                WHEN 'archived' THEN 7
                ELSE 8
            END
        ")
            ->orderByDesc('updated_at');
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
