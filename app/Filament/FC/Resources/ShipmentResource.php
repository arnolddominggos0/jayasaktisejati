<?php

namespace App\Filament\FC\Resources;

use App\Enums\LoadingStatus;
use App\Enums\ServiceType;
use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Enums\TrackStatus;
use App\Filament\FC\Resources\ShipmentResource\Pages\EditShipment;
use App\Filament\FC\Resources\ShipmentResource\Pages\ListShipments;
use App\Filament\FC\Resources\ShipmentResource\Pages\ViewShipment;
use App\Filament\FC\Resources\ShipmentResource\RelationManagers\LoadingSessionsRelationManager;
use App\Filament\FC\Resources\ShipmentResource\RelationManagers\ShipmentUnitsRelationManager;
use App\Models\City;
use App\Models\Depot;
use App\Models\LoadingSession;
use App\Models\Shipment;
use App\Models\UnitInspection;
use App\Models\UnitInspectionItem;
use App\Services\InspectionDraftAutoCreate;
use App\Services\LoadingSessionAutoCreate;
use DomainException;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use App\Services\ShipmentOwnership;
use Filament\Forms\Components\Placeholder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Riwayat Pengiriman';

    protected static ?string $modelLabel = 'Pengiriman';

    protected static ?string $pluralModelLabel = 'Riwayat Pengiriman';

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->isFieldCoordinator() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $depotId = app()->bound('scope.depot_id') ? (int) app('scope.depot_id') : null;
        $userId  = Filament::auth()->id();

        $query = parent::getEloquentQuery()
            ->where('mode', ShipmentMode::Sea->value)
            ->with([
                'customer:id,name',
                'receiver:id,name',
                'originCity:id,name',
                'destinationCity:id,name',
                'latestTrack',
            ]);

        if ($depotId) {
            $query->where(function ($w) use ($depotId, $userId) {
                $w->where('assigned_depot_id', $depotId)
                    ->orWhere('coordinator_id', $userId);
            });
        } else {
            $query->where('coordinator_id', $userId);
        }

        return $query;
    }

    protected static function getNextTrackStatusOptions(Shipment $record): array
    {
        $order = TrackStatus::orderForMode($record->mode);
        $current = $record->currentTrackStatus();
        $currentValue = $current?->value;
        $reached = false;
        $options = [];

        foreach ($order as $status) {
            $isCurrentOrPast = $currentValue && $status->value === $currentValue;
            $isNext = $reached;

            if ($isCurrentOrPast) {
                $reached = true;
            }

            if ($isCurrentOrPast) {
                $label = $status->label() . ' ✓';
                $options[$status->value] = $label;

                continue;
            }

            if ($isNext) {
                $options[$status->value] = '➡ ' . $status->label();

                continue;
            }

            $track = $record->tracks()->where('status', $status->value)->whereNotNull('tracked_at')->exists();
            if ($track) {
                $options[$status->value] = $status->label() . ' ✓';
                $reached = true;

                continue;
            }

            $options[$status->value] = $status->label();
        }

        $finished = TrackStatus::finished();
        if ($current && ! in_array($current, $finished, true)) {
            $options[TrackStatus::Hold->value] = '⚠ ' . TrackStatus::Hold->label();
            $options[TrackStatus::Cancelled->value] = '✕ ' . TrackStatus::Cancelled->label();
        }

        return $options;
    }

    public static function trackUpdateForm(): array
    {
        return [
            Select::make('track_status')
                ->label('Status Lapangan')
                ->options(function (?Shipment $record) {
                    if (! $record) {
                        return collect(TrackStatus::orderSea())->mapWithKeys(fn($e) => [$e->value => $e->label()]);
                    }

                    return static::getNextTrackStatusOptions($record);
                })
                ->default(fn(?Shipment $record) => $record?->nextTrackStatus()?->value)
                ->required()
                ->native(false)
                ->columnSpan(12)
                ->live(),

            Forms\Components\Placeholder::make('loading_gate_warning')
                ->label('')
                ->content('⚠ Shipment ber-rak: Status "Dimuat di Kapal" diupdate otomatis setelah loading checkpoint selesai di AppSheet.')
                ->visible(
                    fn(Forms\Get $get, ?Shipment $record) => $get('track_status') === TrackStatus::UnitLoading->value
                        && $record
                        && LoadingSessionAutoCreate::isRackShipment($record)
                ),

            Checkbox::make('complete_current_step')
                ->label('Step ini sudah selesai & lanjut ke status berikutnya')
                ->visible(fn(Forms\Get $get, ?Shipment $record) => $record?->nextTrackStatus() !== null)
                ->default(true)
                ->columnSpan(12),

            DateTimePicker::make('plan_loading_time_at')
                ->label('Plan Loading Time')
                ->seconds(false)
                ->visible(fn(Forms\Get $get) => $get('track_status') === TrackStatus::Handover->value)
                ->required()
                ->columnSpan(6),

            DateTimePicker::make('plan_closing_time_at')
                ->label('Plan Closing Time')
                ->seconds(false)
                ->visible(fn(Forms\Get $get) => $get('track_status') === TrackStatus::Handover->value)
                ->required()
                ->columnSpan(6),


            Textarea::make('note')
                ->label('Catatan Lapangan')
                ->rows(4)
                ->columnSpan(12)
                ->required(fn(Forms\Get $get) => in_array($get('track_status'), [
                    TrackStatus::Hold->value,
                    TrackStatus::Cancelled->value,
                ], true))
                ->minLength(10),

            Textarea::make('override_reason')
                ->label('Alasan Override MP Check')
                ->rows(3)
                ->visible(
                    fn(Forms\Get $get) => auth_user()?->isSuperAdmin() &&
                        in_array($get('track_status'), [
                            TrackStatus::Stuffing->value,
                            TrackStatus::UnitLoading->value,
                            TrackStatus::Unloading->value,
                        ], true)
                )
                ->required(
                    fn(Forms\Get $get) => auth_user()?->isSuperAdmin() &&
                        in_array($get('track_status'), [
                            TrackStatus::Stuffing->value,
                            TrackStatus::UnitLoading->value,
                            TrackStatus::Unloading->value,
                        ], true)
                )
                ->minLength(20)
                ->helperText('Wajib diisi minimal 20 karakter. Dicatat untuk audit.')
                ->columnSpan(12),

            // ── Checkseet opsional — hanya untuk tahap tanpa form inspeksi ────
            static::optionalChecksheetSchema()
                ->columnSpan(12)
                ->visible(
                    fn(Forms\Get $get): bool =>
                    InspectionDraftAutoCreate::resolveStage(
                        TrackStatus::tryFrom((string) $get('track_status')) ?? TrackStatus::Hold
                    ) === null
                ),
        ];
    }

    /**
     * Reusable inspection form fields for embedding in OperationalTasks updateTrack action.
     *
     * Uses a flat single Repeater (inspection_items_flat) instead of nested Repeaters
     * to work around Filament v3's failure to hydrate inner Repeater items from fillForm().
     *
     * Each row carries: item_id, inspection_id, unit_id, unit_label, category, item_name,
     * result, finding_type, notes.
     *
     * The caller (OperationalTasks) is responsible for:
     *   - Filling 'inspection_stage' and 'inspection_items_flat' via ->fillForm()
     *   - Saving the submitted data to unit_inspection_items / unit_inspections
     */
    public static function inspectionFormFields(): array
    {
        return [
            Hidden::make('inspection_stage'),

            Forms\Components\Section::make('Inspeksi Unit')
                ->description(fn(Get $get): string => match ($get('inspection_stage')) {
                    'pickup'         => 'Pemeriksaan kondisi unit saat pickup dari PDC Asal.',
                    'handover_depot' => 'Pemeriksaan kondisi unit saat handover ke Depo.',
                    'loading'        => 'Pemeriksaan kondisi unit sebelum dimuat ke kontainer.',
                    'unloading'      => 'Pemeriksaan kondisi unit setelah pembongkaran di tujuan.',
                    'selfdrive'      => 'Pemeriksaan kondisi unit sebelum selfdrive ke customer.',
                    'dooring'        => 'Pemeriksaan kondisi unit saat serah terima ke customer.',
                    default          => '',
                })
                ->visible(fn(Get $get): bool => ! empty($get('inspection_stage')))
                ->columnSpanFull()
                ->schema([
                    Repeater::make('inspection_items_flat')
                        ->label('Item Pemeriksaan')
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->columnSpanFull()
                        ->schema([
                            Hidden::make('item_id'),
                            Hidden::make('inspection_id'),
                            Hidden::make('unit_id'),
                            Hidden::make('unit_label'),

                            Grid::make(5)->schema([
                                TextInput::make('unit_label_display')
                                    ->label('Unit')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(1),

                                TextInput::make('category')
                                    ->label('Kategori')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(1),

                                TextInput::make('item_name')
                                    ->label('Item Pemeriksaan')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(2),

                                ToggleButtons::make('result')
                                    ->label('Hasil')
                                    ->options([
                                        UnitInspectionItem::RESULT_OK => 'OK',
                                        UnitInspectionItem::RESULT_NG => 'NG',
                                    ])
                                    ->colors([
                                        UnitInspectionItem::RESULT_OK => 'success',
                                        UnitInspectionItem::RESULT_NG => 'danger',
                                    ])
                                    ->default(UnitInspectionItem::RESULT_OK)
                                    ->required()
                                    ->live()
                                    ->grouped()
                                    ->columnSpan(1),
                            ]),

                            Grid::make(2)
                                ->schema([
                                    Select::make('finding_type')
                                        ->label('Jenis Temuan')
                                        ->options(UnitInspectionItem::FINDING_LABELS)
                                        ->required(fn(Get $get): bool => $get('result') === UnitInspectionItem::RESULT_NG)
                                        ->visible(fn(Get $get): bool => $get('result') === UnitInspectionItem::RESULT_NG)
                                        ->live(),

                                    Textarea::make('notes')
                                        ->label('Catatan / Deskripsi Temuan')
                                        ->rows(2)
                                        ->required(fn(Get $get): bool => $get('result') === UnitInspectionItem::RESULT_NG)
                                        ->visible(fn(Get $get): bool => $get('result') === UnitInspectionItem::RESULT_NG),
                                ])
                                ->visible(fn(Get $get): bool => $get('result') === UnitInspectionItem::RESULT_NG),
                        ]),
                ]),
        ];
    }

    /**
     * Reusable optional checkseet Repeater for quick actions.
     *
     * Unlike trackUpdateForm(), this has no minItems and is never required —
     * FC may submit without filling unit data.  When at least one row IS added
     * the inner fields (model, no_rangka, etc.) remain required, so partial rows
     * are still rejected.
     */
    protected static function optionalChecksheetSchema(): Repeater
    {
        return Repeater::make('checkseet')
            ->label('Checksheet Unit (Opsional)')
            ->helperText('Tambahkan kondisi setiap unit. Bisa dikosongkan jika tidak diperlukan.')
            ->collapsible()
            ->orderColumn(false)
            ->default([])
            ->schema([
                Radio::make('checkseet_status')
                    ->label('Kondisi')
                    ->options(['ok' => 'OK', 'ng' => 'NG'])
                    ->required(),
                TextInput::make('model')
                    ->label('Model')
                    ->required(),
                TextInput::make('no_rangka')
                    ->label('No. Rangka')
                    ->required(),
                TextInput::make('no_mesin')
                    ->label('No. Mesin')
                    ->required(),
                TextInput::make('warna')
                    ->label('Warna')
                    ->required(),
                FileUpload::make('attachments')
                    ->label('Foto Unit')
                    ->disk('public')
                    ->directory('shipment-tracks/checkseet')
                    ->multiple()
                    ->image()
                    ->required(fn(Forms\Get $get) => $get('checkseet_status') === 'ng'),
            ]);
    }

    /**
     * Shared action callback: append a track entry with optional checkseet.
     *
     * - Wraps appendTrack() in a try-catch so DomainExceptions (MP check gate,
     *   invalid transitions) surface as Filament danger notifications.
     * - Sends a warning notification when no checkseet rows were provided.
     * - Sends a success notification listing how many units were recorded.
     */
    protected static function appendTrackWithCheckseet(
        Shipment    $record,
        TrackStatus $status,
        array       $data,
        string      $label,
    ): void {
        $checkseet = ! empty($data['checkseet']) ? $data['checkseet'] : null;

        try {
            $record->appendTrack(
                $status,
                $data['note'] ?? null,
                null,   // location — not collected in quick actions
                null,   // track-level attachments — not collected in quick actions
                null,   // override — quick actions don't support admin override
                $checkseet,
            );
        } catch (DomainException $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        if (empty($checkseet)) {
            Notification::make()
                ->title("{$label} dicatat")
                ->body('Checksheet tidak diisi. Data kondisi unit tidak tersimpan.')
                ->warning()
                ->send();
        } else {
            $count = count($checkseet);
            Notification::make()
                ->title("{$label} dicatat")
                ->body("{$count} unit tercatat dalam checksheet.")
                ->success()
                ->send();
        }
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Pengiriman')
                ->columns(12)
                ->schema([
                    Forms\Components\Placeholder::make('code')
                        ->label('Kode')
                        ->content(fn(Shipment $record) => $record->code)
                        ->columnSpan(4),

                    Forms\Components\Placeholder::make('status')
                        ->label('Status')
                        ->content(fn(Shipment $record) => $record->status?->label() ?? '-')
                        ->columnSpan(4),

                    Forms\Components\Placeholder::make('route')
                        ->label('Rute')
                        ->content(fn(Shipment $record) => ($record->originCity->name ?? '-') . ' → ' . ($record->destinationCity->name ?? '-'))
                        ->columnSpan(12),
                ]),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->badge()
                    ->color(fn(Shipment $record) => $record->mode === ShipmentMode::Sea ? 'primary' : 'warning')
                    ->extraAttributes(['class' => 'font-mono'])
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                IconColumn::make('mode')
                    ->label('Moda')
                    ->icon(fn($state) => ($state instanceof ShipmentMode ? $state->value : (string) $state) === ShipmentMode::Sea->value
                        ? 'heroicon-m-cog-8-tooth' : 'heroicon-m-truck')
                    ->color(fn($state) => ($state instanceof ShipmentMode ? $state->value : (string) $state) === ShipmentMode::Sea->value
                        ? 'primary' : 'warning')
                    ->tooltip(fn($state) => ($state instanceof ShipmentMode ? $state->value : (string) $state) === ShipmentMode::Sea->value
                        ? 'Laut' : 'Darat'),

                TextColumn::make('customer.name')->label('Pengirim')->badge()->searchable()->toggleable(),
                TextColumn::make('receiver.name')->label('Penerima')->badge()->searchable()->toggleable(),

                TextColumn::make('route')
                    ->label('Rute')
                    ->html()
                    ->getStateUsing(function (Shipment $record): string {
                        $o = $record->originCity->name ?? '-';
                        $d = $record->destinationCity->name ?? '-';

                        return "<div class='font-medium'>{$o} &rarr; {$d}</div>";
                    })
                    ->toggleable(),

                TextColumn::make('service_type')
                    ->label('Layanan')
                    ->getStateUsing(fn(Shipment $record) => $record->service_type?->label() ?? (string) $record->service_type ?: '-')
                    ->badge()
                    ->colors([
                        'info' => [ServiceType::SeaFreight->label()],
                        'warning' => [ServiceType::LandTrucking->label(), ServiceType::CarCarrier->label()],
                    ])
                    ->toggleable(),

                TextColumn::make('vehicle_loading')
                    ->label('Muatan')
                    ->getStateUsing(function (Shipment $record) {
                        if ($record->cargo_type?->value === 'vehicle') {
                            return match ($record->vehicle_loading) {
                                'rack' => 'Rack',
                                'flat_rack' => 'Flat Rack',
                                'regular' => 'Reguler',
                                default => $record->vehicle_loading ?: '-',
                            };
                        }

                        return $record->service_option ? strtoupper($record->service_option) : '-';
                    })
                    ->badge()
                    ->toggleable(),

                TextColumn::make('latest_track_status')
                    ->label('Track Status')
                    ->badge()
                    ->formatStateUsing(fn(Shipment $record) => $record->latest_track_status?->label() ?? '-')
                    ->color(fn(Shipment $record) => match ($record->latest_track_status) {
                        TrackStatus::Delivered => 'success',
                        TrackStatus::Cancelled => 'danger',
                        TrackStatus::Hold => 'warning',
                        null => 'gray',
                        default => 'info',
                    })
                    ->sortable(false)
                    ->searchable(false),

                TextColumn::make('loading_status')
                    ->label('Loading')
                    ->badge()
                    ->getStateUsing(function (Shipment $record) {
                        if (! LoadingSessionAutoCreate::isRackShipment($record)) {
                            return '—';
                        }
                        $session = LoadingSession::where('shipment_id', $record->id)
                            ->where('operation_type', 'loading')
                            ->first();
                        if (! $session) {
                            return 'Belum ada';
                        }
                        $status = $session->status instanceof LoadingStatus
                            ? $session->status
                            : LoadingStatus::tryFrom((string) ($session->status ?? ''));

                        return $status?->label() ?? (string) $session->status;
                    })
                    ->color(function (Shipment $record) {
                        if (! LoadingSessionAutoCreate::isRackShipment($record)) {
                            return 'gray';
                        }
                        $session = LoadingSession::where('shipment_id', $record->id)
                            ->where('operation_type', 'loading')
                            ->first();
                        if (! $session) {
                            return 'gray';
                        }

                        $status = $session->status instanceof LoadingStatus
                            ? $session->status
                            : LoadingStatus::tryFrom((string) ($session->status ?? ''));

                        if (! $status) {
                            return 'gray';
                        }

                        return $status->color();
                    })
                    ->color(function (Shipment $record) {
                        if (! LoadingSessionAutoCreate::isRackShipment($record)) {
                            return 'gray';
                        }
                        $session = LoadingSession::where('shipment_id', $record->id)
                            ->where('operation_type', 'loading')
                            ->first();
                        if (! $session) {
                            return 'gray';
                        }

                        return match ($session->status) {
                            LoadingStatus::Completed->value => 'success',
                            LoadingStatus::InProgress->value => 'warning',
                            default => 'gray',
                        };
                    })
                    ->visible(fn() => true)
                    ->toggleable(),

                TextColumn::make('eta')->label('ETA')->badge()->dateTime('d M Y, H:i')->toggleable(),
                TextColumn::make('updated_at')->label('Diubah')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(ShipmentStatus::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()])),
                Tables\Filters\SelectFilter::make('origin_city_id')
                    ->label('Asal')
                    ->options(fn() => City::active()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable(),
                Tables\Filters\SelectFilter::make('destination_city_id')
                    ->label('Tujuan')
                    ->options(fn() => City::active()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable(),
                Tables\Filters\SelectFilter::make('tahun')
                    ->label('Tahun')
                    ->options(
                        fn() => DB::table('shipment_tracks')
                            ->whereIn('status', [TrackStatus::Delivered->value, TrackStatus::Cancelled->value])
                            ->whereNotNull('tracked_at')
                            ->selectRaw('EXTRACT(YEAR FROM tracked_at)::int AS y')
                            ->distinct()
                            ->orderByDesc('y')
                            ->pluck('y', 'y')
                            ->toArray()
                    )
                    ->modifyQueryUsing(
                        fn(Builder $query, array $data): Builder =>
                        filled($data['value'])
                            ? $query->whereHas(
                                'latestTrack',
                                fn(Builder $t) =>
                                $t->whereYear('tracked_at', (int) $data['value'])->whereNotNull('tracked_at')
                            )
                            : $query
                    ),
                Tables\Filters\SelectFilter::make('bulan')
                    ->label('Bulan')
                    ->options([
                        1  => 'Januari',
                        2  => 'Februari',
                        3  => 'Maret',
                        4  => 'April',
                        5  => 'Mei',
                        6  => 'Juni',
                        7  => 'Juli',
                        8  => 'Agustus',
                        9  => 'September',
                        10 => 'Oktober',
                        11 => 'November',
                        12 => 'Desember',
                    ])
                    ->modifyQueryUsing(
                        fn(Builder $query, array $data): Builder =>
                        filled($data['value'])
                            ? $query->whereHas(
                                'latestTrack',
                                fn(Builder $t) =>
                                $t->whereMonth('tracked_at', (int) $data['value'])->whereNotNull('tracked_at')
                            )
                            : $query
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('printWaybill')
                        ->label('Cetak Waybill')
                        ->icon('heroicon-m-printer')
                        ->color('primary')
                        ->url(fn(Shipment $record): string => route('shipments.print.waybill', $record))
                        ->openUrlInNewTab()
                        ->visible(fn(Shipment $record) => auth()->user()?->can('print', $record)),

                    Tables\Actions\Action::make('printPackingList')
                        ->label('Cetak Packing List')
                        ->icon('heroicon-m-clipboard-document-list')
                        ->color('info')
                        ->url(fn(Shipment $record): string => route('shipments.print.packing', $record))
                        ->openUrlInNewTab()
                        ->visible(fn(Shipment $record) => auth()->user()?->can('print', $record)),

                    Tables\Actions\Action::make('printResi')
                        ->label('Cetak Resi')
                        ->icon('heroicon-m-document-text')
                        ->color('gray')
                        ->url(fn(Shipment $record): string => route('shipments.resi', $record))
                        ->openUrlInNewTab()
                        ->visible(fn(Shipment $record) => auth()->user()?->can('print', $record)),
                ])->label('Cetak')->icon('heroicon-m-printer')->color('gray'),
            ])
            ->bulkActions([])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShipments::route('/'),
            'view'  => ViewShipment::route('/{record}'),
            'edit'  => EditShipment::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            ShipmentUnitsRelationManager::class,
            LoadingSessionsRelationManager::class,
        ];
    }
}
