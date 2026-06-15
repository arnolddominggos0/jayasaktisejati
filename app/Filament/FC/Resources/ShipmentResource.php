<?php

namespace App\Filament\FC\Resources;

use App\Enums\LoadingStatus;
use App\Enums\ServiceType;
use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Enums\TrackStatus;
use App\Filament\FC\Resources\ShipmentResource\Pages\EditShipment;
use App\Filament\FC\Resources\ShipmentResource\Pages\InspectUnitPage;
use App\Filament\FC\Resources\ShipmentResource\Pages\ListShipments;
use App\Filament\FC\Resources\ShipmentResource\Pages\ViewShipment;
use App\Filament\FC\Resources\ShipmentResource\RelationManagers\LoadingSessionsRelationManager;
use App\Filament\FC\Resources\ShipmentResource\RelationManagers\ShipmentUnitsRelationManager;
use App\Models\City;
use App\Models\Depot;
use App\Models\LoadingSession;
use App\Models\Shipment;
use App\Services\LoadingSessionAutoCreate;
use DomainException;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static ?string $navigationGroup = 'Manajemen Pengiriman';

    protected static ?string $navigationLabel = 'Pengiriman Ditugaskan';

    protected static ?string $modelLabel = 'Pengiriman';

    protected static ?string $pluralModelLabel = 'Pengiriman Ditugaskan';

    protected static ?string $navigationIcon = 'heroicon-m-truck';

    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->hasRole('field_coordinator') ?? false;
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

    protected static function trackUpdateForm(): array
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
                    fn(Forms\Get $get) => auth_user()?->hasRole('super_admin') &&
                        in_array($get('track_status'), [
                            TrackStatus::Stuffing->value,
                            TrackStatus::UnitLoading->value,
                            TrackStatus::Unloading->value,
                        ], true)
                )
                ->required(
                    fn(Forms\Get $get) => auth_user()?->hasRole('super_admin') &&
                        in_array($get('track_status'), [
                            TrackStatus::Stuffing->value,
                            TrackStatus::UnitLoading->value,
                            TrackStatus::Unloading->value,
                        ], true)
                )
                ->minLength(20)
                ->helperText('Wajib diisi minimal 20 karakter. Dicatat untuk audit.')
                ->columnSpan(12),
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
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('updateTrack')
                    ->label('Update Lapangan')
                    ->icon('heroicon-m-pencil-square')
                    ->color('info')
                    ->form(static::trackUpdateForm())
                    ->action(function (Shipment $record, array $data, $livewire) {
                        $status = TrackStatus::from($data['track_status']);

                        $existingTrack = $record->tracks()
                            ->where('status', $status->value)
                            ->whereNotNull('tracked_at')
                            ->first();

                        if ($existingTrack) {
                            Notification::make()
                                ->title('Status sudah pernah dicapai')
                                ->body("'{$status->label()}' sudah diupdate pada " . $existingTrack->tracked_at->format('d M Y H:i') . '.')
                                ->warning()
                                ->send();

                            return;
                        }

                        if ($status === TrackStatus::UnitLoading && LoadingSessionAutoCreate::isRackShipment($record)) {
                            Notification::make()
                                ->title('Update otomatis via AppSheet')
                                ->body('Status "Dimuat di Kapal" untuk shipment ber-rak diupdate otomatis setelah loading checkpoint selesai di AppSheet.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $override = null;
                        if (auth_user()?->hasRole('super_admin') && ! empty($data['override_reason'])) {
                            $override = ['reason' => $data['override_reason']];
                        }

                        try {
                            $record->appendTrack(
                                $status,
                                $data['note'] ?? null,
                                null,
                                null,
                                $override,
                                $data['checkseet'] ?? null,
                                $data['plan_loading_time_at'] ?? null,
                                $data['plan_closing_time_at'] ?? null,
                            );

                            Notification::make()
                                ->title('Update lapangan tersimpan')
                                ->body("Status diubah ke: {$status->label()}")
                                ->success()
                                ->send();

                            if ($status === TrackStatus::Pickup) {
                                $livewire->redirect(ShipmentResource::getUrl('view', ['record' => $record->getKey()]));
                            }
                        } catch (DomainException $e) {
                            Notification::make()
                                ->title($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('toPending')
                        ->label('Set Menunggu')
                        ->icon('heroicon-m-clock')
                        ->color('gray')
                        ->visible(fn(Shipment $record) => in_array($record->status?->value ?? (string) $record->status, ['draft', 'hold'], true))
                        ->requiresConfirmation()
                        ->action(function (Shipment $record) {
                            $record->update(['status' => ShipmentStatus::Pending->value]);
                            Notification::make()->title('Status di-set ke Menunggu')->success()->send();
                        }),

                    Tables\Actions\Action::make('startPickup')
                        ->label('Mulai Penjemputan')
                        ->icon('heroicon-m-truck')
                        ->color('info')
                        ->visible(fn(Shipment $record) => ($record->status?->value ?? (string) $record->status) === 'pending')
                        ->form([Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Shipment $record, array $data, $livewire) {
                            if (blank($record->coordinator_id)) {
                                $record->forceFill(['coordinator_id' => Filament::auth()->id()])->saveQuietly();
                            }
                            try {
                                $record->appendTrack(TrackStatus::Pickup, $data['note'] ?? null);
                            } catch (DomainException $e) {
                                Notification::make()->title($e->getMessage())->danger()->send();
                                return;
                            }
                            Notification::make()
                                ->title('Penjemputan dicatat')
                                ->body('Silakan lakukan inspeksi pickup untuk setiap unit pada tab Unit & Inspeksi.')
                                ->success()
                                ->send();
                            $livewire->redirect(ShipmentResource::getUrl('view', ['record' => $record->getKey()]));
                        }),

                    Tables\Actions\Action::make('handover')
                        ->label('Handover Depo')
                        ->icon('heroicon-m-building-office')
                        ->color('info')
                        ->visible(fn(Shipment $record) => $record->latest_track_status?->value === TrackStatus::Pickup->value)
                        ->form([Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Shipment $record, array $data) {
                            try {
                                $record->appendTrack(TrackStatus::Handover, $data['note'] ?? null);
                                Notification::make()->title('Handover Depo dicatat')->success()->send();
                            } catch (DomainException $e) {
                                Notification::make()->title($e->getMessage())->danger()->send();
                            }
                        }),

                    Tables\Actions\Action::make('stuffing')
                        ->label('Stuffing & Segel')
                        ->icon('heroicon-m-wrench-screwdriver')
                        ->color('info')
                        ->visible(function (Shipment $record) {
                            if ($record->latest_track_status?->value !== TrackStatus::Handover->value) {
                                return false;
                            }

                            return ! LoadingSessionAutoCreate::isRackShipment($record);
                        })
                        ->form([Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(fn(Shipment $record, array $data) => $record->appendTrack(TrackStatus::Stuffing, $data['note'] ?? null)),

                    Tables\Actions\Action::make('stuffingViaAppSheet')
                        ->label('Loading via AppSheet')
                        ->icon('heroicon-m-device-phone-mobile')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Loading via AppSheet')
                        ->modalDescription('Untuk shipment ber-rack, proses stuffing & loading dilakukan melalui AppSheet (checkpoint). Setelah semua checkpoint selesai dan keputusan final dibuat, status akan otomatis berubah ke "Dimuat di Kapal".')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup')
                        ->visible(function (Shipment $record) {
                            if ($record->latest_track_status?->value !== TrackStatus::Handover->value) {
                                return false;
                            }

                            return LoadingSessionAutoCreate::isRackShipment($record);
                        })
                        ->action(fn() => null),

                    Tables\Actions\Action::make('deliveryToPort')
                        ->label('Antar ke Pelabuhan')
                        ->icon('heroicon-m-arrow-up-right')
                        ->color('info')
                        ->visible(function (Shipment $record) {
                            $last = $record->latest_track_status?->value;
                            if ($last === TrackStatus::Stuffing->value) {
                                return true;
                            }
                            if ($last === TrackStatus::Handover->value && LoadingSessionAutoCreate::isRackShipment($record)) {
                                return true;
                            }

                            return false;
                        })
                        ->form([Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Shipment $record, array $data) {
                            $record->appendTrack(TrackStatus::DeliveryToPort, $data['note'] ?? null);
                            Notification::make()->title('Status: Antar ke Pelabuhan')->success()->send();
                        }),

                    Tables\Actions\Action::make('stacking')
                        ->label('Stacking (Terminal)')
                        ->icon('heroicon-m-rectangle-group')
                        ->color('info')
                        ->visible(fn(Shipment $record) => $record->latest_track_status?->value === TrackStatus::DeliveryToPort->value)
                        ->form([Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Shipment $record, array $data) {
                            $record->appendTrack(TrackStatus::Stacking, $data['note'] ?? null);
                            Notification::make()->title('Status: Stacking')->success()->send();
                        }),

                    Tables\Actions\Action::make('unitLoadingAuto')
                        ->label('Dimuat di Kapal (Otomatis)')
                        ->icon('heroicon-m-arrow-path')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Dimuat di Kapal — Otomatis via AppSheet')
                        ->modalDescription('Status "Dimuat di Kapal" akan otomatis diupdate setelah semua checkpoint loading selesai di AppSheet. Jika shipment ini BUKAN ber-rack, klik "Lewati" untuk update manual.')
                        ->modalSubmitActionLabel('Update Manual (Non-Rack)')
                        ->visible(function (Shipment $record) {
                            if ($record->latest_track_status?->value !== TrackStatus::Stacking->value) {
                                return false;
                            }

                            return ! LoadingSessionAutoCreate::isRackShipment($record);
                        })
                        ->action(function (Shipment $record, array $data) {
                            $record->appendTrack(TrackStatus::UnitLoading, $data['note'] ?? null);
                            Notification::make()->title('Status: Dimuat di Kapal')->success()->send();
                        }),

                    Tables\Actions\Action::make('unitLoadingInfo')
                        ->label('Loading via AppSheet')
                        ->icon('heroicon-m-device-phone-mobile')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Loading Checkpoint — Via AppSheet')
                        ->modalDescription('Untuk shipment ber-rack, status "Dimuat di Kapal" diupdate otomatis setelah loading checkpoint selesai di AppSheet. Tidak perlu input manual.')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup')
                        ->visible(function (Shipment $record) {
                            if ($record->latest_track_status?->value !== TrackStatus::Stacking->value) {
                                return false;
                            }

                            return LoadingSessionAutoCreate::isRackShipment($record);
                        })
                        ->action(fn() => null),

                    Tables\Actions\Action::make('onShip')
                        ->label('On Ship')
                        ->icon('heroicon-m-rocket-launch')
                        ->color('info')
                        ->visible(fn(Shipment $record) => $record->latest_track_status?->value === TrackStatus::UnitLoading->value)
                        ->form([Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(fn(Shipment $record, array $data) => $record->appendTrack(TrackStatus::OnShip, $data['note'] ?? null)),

                    Tables\Actions\Action::make('vesselDepart')
                        ->label('Kapal Berangkat')
                        ->icon('heroicon-m-paper-airplane')
                        ->color('info')
                        ->visible(fn(Shipment $record) => $record->latest_track_status?->value === TrackStatus::OnShip->value)
                        ->form([Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(fn(Shipment $record, array $data) => $record->appendTrack(TrackStatus::VesselDepart, $data['note'] ?? null)),

                    Tables\Actions\Action::make('vesselArrival')
                        ->label('Kapal Tiba')
                        ->icon('heroicon-m-flag')
                        ->color('info')
                        ->visible(fn(Shipment $record) => $record->latest_track_status?->value === TrackStatus::VesselDepart->value)
                        ->form([Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(fn(Shipment $record, array $data) => $record->appendTrack(TrackStatus::VesselArrival, $data['note'] ?? null)),

                    Tables\Actions\Action::make('unloading')
                        ->label('Pembongkaran')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->color('info')
                        ->visible(fn(Shipment $record) => $record->latest_track_status?->value === TrackStatus::VesselArrival->value)
                        ->form([Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Shipment $record, array $data) {
                            try {
                                $record->appendTrack(TrackStatus::Unloading, $data['note'] ?? null);
                                Notification::make()->title('Pembongkaran dicatat')->success()->send();
                            } catch (DomainException $e) {
                                Notification::make()->title($e->getMessage())->danger()->send();
                            }
                        }),

                    Tables\Actions\Action::make('handoverTrucking')
                        ->label('Handover Selfdrive')
                        ->icon('heroicon-m-arrow-trending-up')
                        ->color('info')
                        ->visible(fn(Shipment $record) => $record->latest_track_status?->value === TrackStatus::Unloading->value)
                        ->form([Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(fn(Shipment $record, array $data) => $record->appendTrack(TrackStatus::HandoverTrucking, $data['note'] ?? null)),

                    Tables\Actions\Action::make('deliveryToCustomer')
                        ->label('Antar ke Customer')
                        ->icon('heroicon-m-user')
                        ->color('info')
                        ->visible(fn(Shipment $record) => $record->latest_track_status?->value === TrackStatus::Unloading->value)
                        ->form([Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Shipment $record, array $data) {
                            try {
                                $record->appendTrack(TrackStatus::DeliveryToCustomer, $data['note'] ?? null);
                                Notification::make()->title('Antar ke Customer dicatat')->success()->send();
                            } catch (DomainException $e) {
                                Notification::make()->title($e->getMessage())->danger()->send();
                            }
                        }),

                    Tables\Actions\Action::make('markDelivered')
                        ->label('Tandai Terkirim')
                        ->icon('heroicon-m-check-badge')
                        ->color('success')
                        ->visible(fn(Shipment $record) => $record->latest_track_status?->value === TrackStatus::DeliveryToCustomer->value)
                        ->form([Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Shipment $record, array $data) {
                            $record->appendTrack(TrackStatus::Delivered, $data['note'] ?? 'Terkirim');
                            Notification::make()->title('Shipment terkirim!')->success()->send();
                        }),

                    Tables\Actions\Action::make('hold')
                        ->label('Tahan')
                        ->icon('heroicon-m-pause-circle')
                        ->color('warning')
                        ->visible(fn(Shipment $record) => $record->latest_track_status !== TrackStatus::Hold
                            && ! in_array($record->latest_track_status, [TrackStatus::Delivered, TrackStatus::Cancelled], true)
                            && $record->latest_track_status !== null)
                        ->form([Textarea::make('note')->label('Alasan')->rows(3)->required()])
                        ->action(fn(Shipment $record, array $data) => $record->appendTrack(TrackStatus::Hold, $data['note'])),

                    Tables\Actions\Action::make('cancel')
                        ->label('Batalkan')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->visible(fn(Shipment $record) => $record->canCancel())
                        ->form([Textarea::make('note')->label('Alasan')->rows(3)->required()])
                        ->requiresConfirmation()
                        ->action(function (Shipment $record, array $data) {
                            $record->appendTrack(TrackStatus::Cancelled, $data['note']);
                            $record->forceFill([
                                'cancelled_at' => now(),
                                'cancelled_by' => Filament::auth()->id(),
                            ])->save();
                        }),
                ])->label('Aksi Status')->icon('heroicon-m-cog')->color('secondary'),
            ])
            ->bulkActions([])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'        => ListShipments::route('/'),
            'view'         => ViewShipment::route('/{record}'),
            'edit'         => EditShipment::route('/{record}/edit'),
            'inspect-unit' => InspectUnitPage::route('/{record}/units/{unit}/inspect'),
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
