<?php

namespace App\Filament\FC\Pages;

use App\Enums\CargoType;
use App\Enums\MPCheckStatus;
use App\Enums\ShipmentStatus;
use App\Enums\TrackStatus;
use App\Filament\FC\Resources\BriefingSessionResource;
use App\Filament\FC\Resources\ContainerReadinessSessionResource;
use App\Filament\FC\Resources\ShipmentResource;
use App\Models\BriefingSession;
use App\Models\ContainerReadinessSession;
use App\Models\Depot;
use App\Models\Unit;
use App\Models\UnitInspection;
use App\Services\DailyBriefingGate;
use App\Services\InspectionDraftAutoCreate;
use App\Services\LoadingSessionAutoCreate;
use App\Services\ShipmentOperationalGateResolver;
use App\Services\ShipmentOwnership;
use DomainException;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Textarea as FormTextarea;
use Filament\Forms\Components\TextInput as FormTextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OperationalTasks extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationGroup = 'Operasional Lapangan';

    protected static ?string $navigationLabel = 'Tugas Operasional';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.fc.pages.operational-tasks';

    public function getHeading(): string
    {
        return 'Tugas Operasional';
    }

    public function getSubheading(): ?string
    {
        return 'Pekerjaan aktif yang memerlukan tindakan dari depot ini.';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isFieldCoordinator() ?? false;
    }

    // ── Scope resolution ──────────────────────────────────────────────────────

    private function depotId(): ?int
    {
        if (app()->bound('scope.depot_id')) {
            return (int) app('scope.depot_id');
        }

        return session('fc.active_depot_id');
    }

    private function portId(): ?int
    {
        if (app()->bound('scope.port_id')) {
            return (int) app('scope.port_id');
        }

        $depotId = $this->depotId();
        if ($depotId) {
            $depot = Depot::query()->find($depotId);
            return $depot?->port_id;
        }

        return null;
    }

    // ── Daily Setup (Setup Hari Ini) ──────────────────────────────────────────

    public function getDailySetup(): array
    {
        $depotId = $this->depotId();

        // Briefing session hari ini untuk depot ini
        $briefing = $depotId
            ? BriefingSession::query()
            ->where('depot_id', $depotId)
            ->whereDate('date', Carbon::today())
            ->select('id', 'mp_check_status', 'summary_sufficient', 'summary_headcount')
            ->latest()
            ->first()
            : null;

        // Container readiness hari ini (global, tidak ada depot scope)
        $container = DB::table('container_readiness_sessions')
            ->whereDate('session_date', Carbon::today())
            ->select('id', 'summary_sufficient', 'container_need', 'container_available')
            ->first();

        // Resolve MPCheckStatus label
        $briefingStatusLabel = null;
        if ($briefing) {
            $status = $briefing->mp_check_status instanceof MPCheckStatus
                ? $briefing->mp_check_status
                : MPCheckStatus::tryFrom((string) $briefing->mp_check_status);
            $briefingStatusLabel = $status?->label() ?? (string) $briefing->mp_check_status;
        }

        return [
            'briefing' => [
                'exists' => $briefing !== null,
                'status_label' => $briefingStatusLabel,
                'create_url' => BriefingSessionResource::getUrl('create'),
                'view_url' => $briefing
                    ? BriefingSessionResource::getUrl('view', ['record' => $briefing->id])
                    : null,
            ],
            'container' => [
                'exists' => $container !== null,
                'is_ready' => $container ? (bool) $container->summary_sufficient : null,
                'create_url' => ContainerReadinessSessionResource::getUrl('create'),
                'edit_url' => $container
                    ? ContainerReadinessSessionResource::getUrl('edit', ['record' => $container->id])
                    : null,
            ],
        ];
    }

    public function getOperationalNotifications(): \Illuminate\Database\Eloquent\Collection
    {
        $user = auth()->user();

        if (! $user) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        return $user->unreadNotifications()
            ->where('data->format', 'filament')
            ->get();
    }

    public function getDailySummary(): array
    {
        $units = $this->getTableQuery()->get();

        $shipments = $units
            ->map(fn(Unit $u) => $u->shipment)
            ->filter()
            ->unique('id');

        $voyages = $shipments
            ->map(fn($s) => $s->getRelation('voyage'))
            ->filter()
            ->unique('id');

        $voyageDisplay = match (true) {
            $voyages->isEmpty() => '—',
            $voyages->count() === 1 => 'V.' . $voyages->first()->voyage_no,
            default => $voyages->count() . ' Voyage Aktif',
        };

        $nearestEtd = $shipments
            ->map(fn($s) => $s->getRelation('voyage')?->etd)
            ->filter()
            ->min();

        return [
            'shipment_count' => $shipments->count(),
            'unit_count' => $units->count(),
            'voyage_display' => $voyageDisplay,
            'etd_display' => $nearestEtd?->translatedFormat('d M Y') ?? '—',
        ];
    }

    // ── Status partitions ─────────────────────────────────────────────────────

    /** Statuses where the origin FC is the executor (pre-transfer phase). */
    private static function originStatuses(): array
    {
        return [
            TrackStatus::Pickup->value,
            TrackStatus::Handover->value,
            TrackStatus::Stuffing->value,
            TrackStatus::DeliveryToPort->value,
            TrackStatus::Stacking->value,
            TrackStatus::UnitLoading->value,
            TrackStatus::OnShip->value,
            TrackStatus::VesselDepart->value,
        ];
    }

    /** Statuses where the destination FC is the executor (post-transfer phase). */
    private static function destActiveStatuses(): array
    {
        return [
            TrackStatus::VesselArrival->value,
            TrackStatus::Unloading->value,
            TrackStatus::HandoverTrucking->value,
            TrackStatus::DeliveryToCustomer->value,
        ];
    }

    protected function getTableQuery(): Builder
    {
        $depotId = $this->depotId();
        $portId = $this->portId();
        $userId = auth()->id();
        return Unit::query()
            ->select('units.*')
            ->with($this->eagerLoads())
            ->whereHas('shipment', function (Builder $s) use ($depotId, $portId, $userId) {
                $s->where('mode', 'sea')
                    ->whereNotIn('status', ['draft', 'delivered', 'cancelled'])
                    ->where(function (Builder $outer) use ($depotId, $portId, $userId) {

                        // ARM A — origin executor (canEdit pre_transfer):
                        //   ownership (depot OR coordinator) AND phase is pre-transfer
                        $outer->where(function (Builder $a) use ($depotId, $userId) {
                            $a->where(function (Builder $own) use ($depotId, $userId) {
                                if ($depotId) {
                                    $own->where('assigned_depot_id', $depotId)
                                        ->orWhere('coordinator_id', $userId);
                                } else {
                                    $own->where('coordinator_id', $userId);
                                }
                            })->where(function (Builder $phase) {
                                $phase
                                    ->whereDoesntHave('tracks', fn(Builder $t) => $t->whereNotNull('tracked_at'))
                                    ->orWhereHas(
                                        'latestTrack',
                                        fn(Builder $t) => $t->whereIn('status', self::originStatuses())
                                    );
                            });
                        });

                        // ARM B — destination executor (canEdit post_transfer):
                        //   pod resolves to this depot's port AND phase is post-transfer
                        if ($portId) {
                            $outer->orWhere(function (Builder $b) use ($portId) {
                                $b->where(function (Builder $pod) use ($portId) {
                                    $pod->where('pod_id', $portId)
                                        ->orWhereExists(
                                            fn($v) => $v->from('voyages')
                                                ->whereColumn('voyages.id', 'shipments.voyage_id')
                                                ->where('voyages.pod_id', $portId)
                                        );
                                })->whereHas(
                                    'latestTrack',
                                    fn(Builder $t) => $t->whereIn('status', self::destActiveStatuses())
                                );
                            });
                        }

                        // ARM C — hold: visible to any FC with ownership over this shipment.
                        //   Phase-gated actions (canEdit) still block unauthorized mutations.
                        $outer->orWhere(function (Builder $c) use ($depotId, $portId, $userId) {
                            $c->whereHas(
                                'latestTrack',
                                fn(Builder $t) => $t->where('status', TrackStatus::Hold->value)
                            )->where(function (Builder $ownAny) use ($depotId, $portId, $userId) {
                                if ($depotId) {
                                    $ownAny->where('assigned_depot_id', $depotId)
                                        ->orWhere('coordinator_id', $userId);
                                } else {
                                    $ownAny->where('coordinator_id', $userId);
                                }
                                if ($portId) {
                                    $ownAny->orWhere(function (Builder $dest) use ($portId) {
                                        $dest->where('pod_id', $portId)
                                            ->orWhereExists(
                                                fn($v) => $v->from('voyages')
                                                    ->whereColumn('voyages.id', 'shipments.voyage_id')
                                                    ->where('voyages.pod_id', $portId)
                                            );
                                    });
                                }
                            });
                        });
                    });
            })
            ->orderByDesc(
                DB::raw('(SELECT tracked_at FROM shipment_tracks
                          WHERE shipment_id = units.shipment_id
                            AND tracked_at IS NOT NULL
                          ORDER BY tracked_at DESC LIMIT 1)')
            );
    }

    // ── Eager loads (Scope 7: hindari N+1 — Unit + Shipment + ShipmentTrack) ──

    private function eagerLoads(): array
    {
        return [
            'shipment',
            'shipment.voyage:id,voyage_no,etd,eta',
            'shipment.voyage.vessel:id,name',
            'shipment.latestTrack',
            'shipment.customer:id,name',
        ];
    }

    // ── Table definition ──────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([

                TextColumn::make('queue_gate')
                    ->label('Gate')
                    ->badge()
                    ->getStateUsing(
                        fn(Unit $record): string => ShipmentOperationalGateResolver::resolve($record->shipment) === ShipmentOperationalGateResolver::DESTINATION
                            ? 'TUJUAN'
                            : 'ASAL'
                    )
                    ->color(fn(string $state): string => $state === 'TUJUAN' ? 'info' : 'warning'),
                TextColumn::make('sppb_no')
                    ->label('No. SPPB')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->orWhereHas(
                            'shipment',
                            fn(Builder $s) => $s->where('doc_number', 'like', "%{$search}%")
                        );
                    })
                    ->copyable()
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->getStateUsing(fn(Unit $record): string => $record->shipment?->doc_number ?? '—')
                    ->url(
                        fn(Unit $record): string => OperationalShipmentPage::getUrl(['record' => $record->shipment_id])
                    )
                    ->openUrlInNewTab(),

                // Shipment Code tetap ada (Scope 2: "boleh digunakan secara
                // internal") — bukan lagi kolom pertama.
                TextColumn::make('shipment_code')
                    ->label('Shipment')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->orWhereHas(
                            'shipment',
                            fn(Builder $s) => $s->where('code', 'like', "%{$search}%")
                        );
                    })
                    ->copyable()
                    ->fontFamily('mono')
                    ->color('gray')
                    ->getStateUsing(fn(Unit $record): string => $record->shipment?->code ?? '—')
                    ->url(
                        fn(Unit $record): string => OperationalShipmentPage::getUrl(['record' => $record->shipment_id])
                    )
                    ->openUrlInNewTab(),
                TextColumn::make('chassis_no')
                    ->label('Unit')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->default('—')
                    ->description(fn(Unit $record): ?string => $record->model_no),

                TextColumn::make('shipment_customer')
                    ->label('Customer')
                    ->default('—')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->orWhereHas(
                            'shipment.customer',
                            fn(Builder $c) => $c->where('name', 'like', "%{$search}%")
                        );
                    })
                    ->getStateUsing(fn(Unit $record): string => $record->shipment?->customer?->name ?? '—'),

                TextColumn::make('latestTrack.status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn(Unit $record) => $record->shipment?->latestTrack?->status)
                    ->formatStateUsing(
                        fn($state): string => $state instanceof TrackStatus
                            ? $state->label()
                            : (TrackStatus::tryFrom((string) $state)?->label() ?? ((string) $state ?: '—'))
                    )
                    ->color(fn($state): string => match ((string) ($state instanceof TrackStatus ? $state->value : ($state ?? ''))) {
                        TrackStatus::Pickup->value => 'gray',
                        TrackStatus::Handover->value => 'gray',
                        TrackStatus::Stuffing->value,
                        TrackStatus::DeliveryToPort->value,
                        TrackStatus::Stacking->value,
                        TrackStatus::UnitLoading->value => 'warning',
                        TrackStatus::OnShip->value,
                        TrackStatus::VesselDepart->value => 'info',
                        TrackStatus::VesselArrival->value => 'primary',
                        TrackStatus::Unloading->value => 'warning',
                        TrackStatus::HandoverTrucking->value => 'primary',
                        TrackStatus::DeliveryToCustomer->value => 'success',
                        TrackStatus::Delivered->value => 'success',
                        TrackStatus::Hold->value => 'danger',
                        TrackStatus::Cancelled->value => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('voyage_no_display')
                    ->label('Voyage')
                    ->getStateUsing(
                        fn(Unit $record): string => blank($record->shipment?->getRelation('voyage')?->voyage_no)
                            ? '—'
                            : 'V.' . $record->shipment->getRelation('voyage')->voyage_no
                    )
                    ->badge()
                    ->color('gray'),

                TextColumn::make('voyage_eta_display')
                    ->label('ETA')
                    ->getStateUsing(
                        fn(Unit $record): string => blank($record->shipment?->getRelation('voyage')?->eta)
                            ? '—'
                            : \Carbon\Carbon::parse($record->shipment->getRelation('voyage')->eta)->format('d M Y')
                    ),

                TextColumn::make('latestTrack.tracked_at')
                    ->label('Diperbarui')
                    ->getStateUsing(fn(Unit $record) => $record->shipment?->latestTrack?->tracked_at)
                    ->formatStateUsing(
                        fn($state): string => blank($state)
                            ? '—'
                            : \Carbon\Carbon::parse($state)->format('d M Y H:i')
                    ),
            ])
            ->actions([
                Action::make('inspeksi')
                    ->label('Inspeksi')
                    ->icon('heroicon-m-clipboard-document-check')
                    ->color('primary')
                    ->visible(function (Unit $record): bool {
                        $shipment = $record->shipment;
                        if (! $shipment || ! ShipmentOwnership::canView(Filament::auth()->user(), $shipment)) {
                            return false;
                        }

                        $status = $shipment->currentTrackStatus();
                        $stage = $status ? InspectionDraftAutoCreate::resolveStage($status) : null;

                        if (! $stage) {
                            $nextStatus = $shipment->nextTrackStatus();
                            $stage = $nextStatus ? InspectionDraftAutoCreate::resolveStage($nextStatus) : null;
                        }

                        return $stage !== null;
                    })
                    ->url(fn(Unit $record): string => InspectUnitPage::getUrl([
                        'record' => $record->shipment_id,
                        'unit' => $record->id,
                    ]))
                    ->openUrlInNewTab(),

                ActionGroup::make([

                    Action::make('toPending')
                        ->label('Set Menunggu')
                        ->icon('heroicon-m-clock')
                        ->color('gray')
                        ->visible(fn(Unit $record) => in_array(
                            $record->shipment?->status?->value ?? (string) $record->shipment?->status,
                            ['draft', 'hold'],
                            true
                        ))
                        ->requiresConfirmation()
                        ->action(function (Unit $record) {
                            $record->shipment->update(['status' => ShipmentStatus::Pending->value]);
                            Notification::make()->title('Status di-set ke Menunggu')->success()->send();
                        }),

                    Action::make('recordFieldNotes')
                        ->label('Catatan & Checksheet Lapangan')
                        ->icon('heroicon-m-pencil-square')
                        ->color('gray')
                        ->visible(fn(Unit $record) => ShipmentOwnership::canEdit(Filament::auth()->user(), $record->shipment))
                        ->form(fn(Unit $record) => array_merge(
                        ShipmentResource::trackUpdateForm(),
                        ShipmentResource::inspectionStatusFields(),
                    ))
                    ->fillForm(function (Unit $record): array {
                        $shipment = $record->shipment;
                        $nextStatus = $shipment->nextTrackStatus();
                        $stage = $nextStatus ? InspectionDraftAutoCreate::resolveStage($nextStatus) : null;

                        $data = [
                            'track_status' => $nextStatus?->value,
                            'inspection_stage' => $stage,
                        ];

                        if ($stage && $nextStatus) {
                            try {
                                InspectionDraftAutoCreate::ensureForShipmentAndStage($shipment, $stage);
                            } catch (\Throwable $e) {
                                Log::error('FC inspection draft generation failed', [
                                    'shipment_id' => $shipment->id,
                                    'stage' => $stage,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }

                        return $data;
                    })
                    ->action(function (Unit $record, array $data, $livewire) {
                        $shipment = $record->shipment;
                        abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $shipment), 403);
                        $status = TrackStatus::from($data['track_status']);

                        $existing = $shipment->tracks()
                            ->where('status', $status->value)
                            ->whereNotNull('tracked_at')
                            ->first();

                        if ($existing) {
                            Notification::make()
                                ->title('Status sudah pernah dicapai')
                                ->body("'{$status->label()}' sudah diupdate pada " . $existing->tracked_at->format('d M Y H:i') . '.')
                                ->warning()
                                ->send();

                            return;
                        }

                        if ($status === TrackStatus::UnitLoading && LoadingSessionAutoCreate::isRackShipment($shipment)) {
                            Notification::make()
                                ->title('Update otomatis via AppSheet')
                                ->body('Status "Dimuat di Kapal" diupdate otomatis setelah loading checkpoint selesai.')
                                ->warning()
                                ->send();

                            return;
                        }
                        if ($status === TrackStatus::Stuffing) {
                            $blockReason = $shipment->assigned_depot_id
                                ? DailyBriefingGate::blockReason($shipment->assigned_depot_id)
                                : 'Operasional hari ini belum dibuka. Silakan selesaikan Briefing Harian terlebih dahulu.';

                            if ($blockReason) {
                                Notification::make()->title($blockReason)->warning()->send();

                                return;
                            }
                        }
                        try {
                            $savedTrack = $shipment->appendTrack(
                                $status,
                                $data['note'] ?? null,
                                null,
                                null,
                                null,
                                $data['checkseet'] ?? null,
                                $data['plan_loading_time_at'] ?? null,
                                $data['plan_closing_time_at'] ?? null,
                            );
                            $inspStage = $data['inspection_stage'] ?? null;
                            if ($inspStage) {
                                $checkRefs = UnitInspection::query()
                                    ->where('stage', $inspStage)
                                    ->whereIn('unit_id', $shipment->units()->pluck('id'))
                                    ->get()
                                    ->map(fn(UnitInspection $i) => [
                                        'unit_id' => $i->unit_id,
                                        'inspection_id' => $i->id,
                                        'stage' => $i->stage,
                                        'status' => $i->status,
                                        'gate_decision' => $i->gate_decision,
                                    ])
                                    ->all();

                                if (! empty($checkRefs)) {
                                    $savedTrack->updateQuietly([
                                        'check_result' => ['unit_inspections' => $checkRefs],
                                    ]);
                                }
                            }

                            Notification::make()
                                ->title('Catatan lapangan tersimpan')
                                ->body("Status: {$status->label()}")
                                ->success()
                                ->send();

                            if ($status === TrackStatus::Pickup) {
                                $livewire->redirect(OperationalShipmentPage::getUrl(['record' => $shipment->getKey()]));
                            }
                        } catch (DomainException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),

                    Action::make('startPickup')
                        ->label('Penjemputan')
                        ->icon('heroicon-m-truck')
                        ->color('info')
                        ->visible(
                            fn(Unit $record) => (($record->shipment?->status?->value ?? (string) $record->shipment?->status) === 'pending')
                                && ShipmentOwnership::canEdit(Filament::auth()->user(), $record->shipment)
                        )
                        ->form([Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Unit $record, array $data, $livewire) {
                            $shipment = $record->shipment;
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $shipment), 403);
                            if (blank($shipment->coordinator_id)) {
                                $shipment->forceFill(['coordinator_id' => Filament::auth()->id()])->saveQuietly();
                            }
                            try {
                                $shipment->appendTrack(TrackStatus::Pickup, $data['note'] ?? null);
                            } catch (DomainException $e) {
                                Notification::make()->title($e->getMessage())->danger()->send();

                                return;
                            }
                            Notification::make()
                                ->title('Penjemputan dicatat')
                                ->body('Lakukan inspeksi pickup untuk setiap unit di halaman detail.')
                                ->success()
                                ->send();
                            $livewire->redirect(OperationalShipmentPage::getUrl(['record' => $shipment->getKey()]));
                        }),

                    Action::make('handover')
                        ->label('Handover Depo')
                        ->icon('heroicon-m-building-office')
                        ->color('info')
                        ->visible(
                            fn(Unit $record) => $record->shipment?->latest_track_status?->value === TrackStatus::Pickup->value
                                && ShipmentOwnership::canEdit(Filament::auth()->user(), $record->shipment)
                        )
                        ->fillForm(function (Unit $record): array {
                            $shipment = $record->shipment;
                            $isVehicle = ($shipment->cargo_type instanceof CargoType)
                                ? $shipment->cargo_type === CargoType::Vehicle
                                : $shipment->cargo_type === CargoType::Vehicle->value;

                            $unitContainers = [];
                            $containerInfo = '';

                            if ($isVehicle) {
                                $session = ContainerReadinessSession::whereDate('session_date', today())->first();
                                $numbers = $session?->container_number_list ?? [];
                                $containerInfo = empty($numbers)
                                    ? '(Belum dikonfigurasi — isi nomor container secara manual)'
                                    : implode('  ·  ', $numbers);

                                $unitContainers = $shipment->units()->orderBy('id')->get()
                                    ->map(fn($u) => [
                                        'unit_id' => $u->id,
                                        'chassis_no' => $u->chassis_no ?? '—',
                                        'model_no' => $u->model_no ?? '—',
                                        'container_display' => $u->container_display ?? '',
                                    ])->all();
                            }

                            return [
                                'sjkb_no' => '',
                                'yard_slot' => '',
                                'note' => '',
                                'vehicle_loading' => $shipment->vehicle_loading ?? '',
                                'container_info' => $containerInfo,
                                'unit_containers' => $unitContainers,
                            ];
                        })
                        ->form(function (Unit $record): array {
                            $shipment = $record->shipment;
                            $isVehicle = ($shipment->cargo_type instanceof CargoType)
                                ? $shipment->cargo_type === CargoType::Vehicle
                                : $shipment->cargo_type === CargoType::Vehicle->value;

                            $baseFields = [
                                Section::make('Informasi Handover')->schema([
                                    FormTextInput::make('sjkb_no')
                                        ->label('Nomor SJKB')
                                        ->required()
                                        ->maxLength(120),
                                    FormTextInput::make('yard_slot')
                                        ->label('Yard Slot')
                                        ->placeholder('A-01 / B-03')
                                        ->maxLength(50),
                                    FormTextarea::make('note')
                                        ->label('Catatan')
                                        ->rows(2),
                                ])->columns(3),
                            ];

                            if (! $isVehicle) {
                                return $baseFields;
                            }

                            return array_merge($baseFields, [
                                Section::make('Planning Loading')
                                    ->description('Tentukan metode muat dan nomor container untuk setiap unit.')
                                    ->schema([
                                        ToggleButtons::make('vehicle_loading')
                                            ->label('Metode Muat Unit')
                                            ->options([
                                                'regular' => 'Reguler',
                                                'rack' => 'Rack',
                                                'flat_rack' => 'Flat Rack',
                                            ])
                                            ->colors([
                                                'regular' => 'info',
                                                'rack' => 'warning',
                                                'flat_rack' => 'warning',
                                            ])
                                            ->inline()
                                            ->required()
                                            ->columnSpanFull(),

                                        Placeholder::make('container_info')
                                            ->label('Container Tersedia Hari Ini')
                                            ->content(
                                                fn(Get $get): string => $get('container_info') ?: '(Belum dikonfigurasi)'
                                            )
                                            ->columnSpanFull(),

                                        Repeater::make('unit_containers')
                                            ->label('Unit Kendaraan')
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->columnSpanFull()
                                            ->schema([
                                                Hidden::make('unit_id'),
                                                FormTextInput::make('chassis_no')
                                                    ->label('Chassis No')
                                                    ->disabled()
                                                    ->dehydrated(false)
                                                    ->extraAttributes(['style' => 'font-family:monospace'])
                                                    ->columnSpan(4),
                                                FormTextInput::make('model_no')
                                                    ->label('Model')
                                                    ->disabled()
                                                    ->dehydrated(false)
                                                    ->columnSpan(4),
                                                FormTextInput::make('container_display')
                                                    ->label('No. Container')
                                                    ->maxLength(20)
                                                    ->placeholder('TGHU1234567')
                                                    ->extraAttributes(['style' => 'font-family:monospace; text-transform:uppercase'])
                                                    ->columnSpan(4),
                                            ])
                                            ->columns(12),
                                    ]),
                            ]);
                        })
                        ->action(function (Unit $record, array $data, $livewire) {
                            $shipment = $record->shipment;
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $shipment), 403);
                            try {
                                $isVehicle = ($shipment->cargo_type instanceof CargoType)
                                    ? $shipment->cargo_type === CargoType::Vehicle
                                    : $shipment->cargo_type === CargoType::Vehicle->value;

                                // ── Metode Muat: simpan vehicle_loading ─────────────────
                                if ($isVehicle && ! empty($data['vehicle_loading'])) {
                                    $shipment->forceFill(['vehicle_loading' => $data['vehicle_loading']])->saveQuietly();
                                }

                                // ── Planning Loading: assign container per unit ──────────
                                if ($isVehicle && ! empty($data['unit_containers'])) {
                                    foreach ($data['unit_containers'] as $row) {
                                        $unitId = $row['unit_id'] ?? null;
                                        $containerNo = strtoupper(trim($row['container_display'] ?? ''));
                                        if ($unitId) {
                                            $shipment->units()->whereKey($unitId)->update([
                                                'container_display' => $containerNo ?: null,
                                            ]);
                                        }
                                    }
                                }

                                // ── Bulk SJKB to all units ───────────────────────────────
                                $shipment->units()->update(['sjkb_no' => $data['sjkb_no']]);

                                $shipment->appendTrack(
                                    TrackStatus::Handover,
                                    $data['note'] ?? null,
                                    $data['yard_slot'] ?? null,
                                );

                                Notification::make()
                                    ->title('Handover Depo dicatat')
                                    ->body(
                                        $isVehicle
                                            ? 'Container assignment tersimpan. Selesaikan inspeksi unit sebelum Stuffing.'
                                            : 'Selesaikan inspeksi unit sebelum proses stuffing.'
                                    )
                                    ->success()
                                    ->send();

                                $livewire->redirect(OperationalShipmentPage::getUrl(['record' => $shipment->getKey()]));
                            } catch (DomainException $e) {
                                Notification::make()->title($e->getMessage())->danger()->send();
                            }
                        }),
                    Action::make('planningLoading')
                        ->label('Planning Loading')
                        ->icon('heroicon-m-cube')
                        ->color('warning')
                        ->visible(function (Unit $record) {
                            $shipment = $record->shipment;
                            if (! $shipment || ! ShipmentOwnership::canEdit(Filament::auth()->user(), $shipment)) {
                                return false;
                            }
                            if ($shipment->latest_track_status?->value !== TrackStatus::Handover->value) {
                                return false;
                            }

                            return ! $shipment->isContainerAssignmentComplete();
                        })
                        ->url(fn() => ContainerAllocationWorkspace::getUrl()),
                    Action::make('stuffing')
                        ->label('Stuffing & Segel')
                        ->icon('heroicon-m-wrench-screwdriver')
                        ->color('info')
                        ->visible(function (Unit $record) {
                            $shipment = $record->shipment;
                            if (! $shipment || ! ShipmentOwnership::canEdit(Filament::auth()->user(), $shipment)) {
                                return false;
                            }
                            if ($shipment->latest_track_status?->value !== TrackStatus::Handover->value) {
                                return false;
                            }
                            if (! $shipment->isHandoverInspectionCleared()) {
                                return false;
                            }
                            if (! LoadingSessionAutoCreate::isRackShipment($shipment)) {
                                return $shipment->isContainerAssignmentComplete();
                            }

                            return false;
                        })
                        ->form([Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Unit $record, array $data) {
                            $shipment = $record->shipment;
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $shipment), 403);
                            $blockReason = $shipment->assigned_depot_id
                                ? DailyBriefingGate::blockReason($shipment->assigned_depot_id)
                                : 'Operasional hari ini belum dibuka. Silakan selesaikan Briefing Harian terlebih dahulu.';

                            if ($blockReason) {
                                Notification::make()->title($blockReason)->warning()->send();

                                return;
                            }

                            $shipment->appendTrack(TrackStatus::Stuffing, $data['note'] ?? null);
                            Notification::make()->title('Stuffing dicatat')->success()->send();
                        }),

                    Action::make('stuffingViaAppSheet')
                        ->label('Loading via AppSheet')
                        ->icon('heroicon-m-device-phone-mobile')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Loading via AppSheet')
                        ->modalDescription('Untuk shipment ber-rack, proses stuffing & loading dilakukan melalui AppSheet. Setelah semua checkpoint selesai, status otomatis berubah ke "Dimuat di Kapal".')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup')
                        ->visible(function (Unit $record) {
                            $shipment = $record->shipment;
                            if (! $shipment || $shipment->latest_track_status?->value !== TrackStatus::Handover->value) {
                                return false;
                            }
                            if (! $shipment->isHandoverInspectionCleared()) {
                                return false;
                            }

                            return LoadingSessionAutoCreate::isRackShipment($shipment);
                        })
                        ->action(fn() => null),

                    Action::make('deliveryToPort')
                        ->label('Antar ke Pelabuhan')
                        ->icon('heroicon-m-arrow-up-right')
                        ->color('info')
                        ->visible(function (Unit $record) {
                            $shipment = $record->shipment;
                            if (! $shipment || ! ShipmentOwnership::canEdit(Filament::auth()->user(), $shipment)) {
                                return false;
                            }
                            $last = $shipment->latest_track_status?->value;
                            if ($last === TrackStatus::Stuffing->value) {
                                return true;
                            }
                            if ($last === TrackStatus::Handover->value && LoadingSessionAutoCreate::isRackShipment($shipment)) {
                                return $shipment->isHandoverInspectionCleared();
                            }

                            return false;
                        })
                        ->form([Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Unit $record, array $data) {
                            $shipment = $record->shipment;
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $shipment), 403);
                            $shipment->appendTrack(TrackStatus::DeliveryToPort, $data['note'] ?? null);
                            Notification::make()->title('Antar ke Pelabuhan dicatat')->success()->send();
                        }),

                    Action::make('stacking')
                        ->label('Stacking (Terminal)')
                        ->icon('heroicon-m-rectangle-group')
                        ->color('info')
                        ->visible(
                            fn(Unit $record) => $record->shipment?->latest_track_status?->value === TrackStatus::DeliveryToPort->value
                                && ShipmentOwnership::canEdit(Filament::auth()->user(), $record->shipment)
                        )
                        ->form([Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Unit $record, array $data) {
                            $shipment = $record->shipment;
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $shipment), 403);
                            $shipment->appendTrack(TrackStatus::Stacking, $data['note'] ?? null);
                            Notification::make()->title('Stacking dicatat')->success()->send();
                        }),

                    Action::make('unitLoadingAuto')
                        ->label('Dimuat di Kapal')
                        ->icon('heroicon-m-arrow-path')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(function (Unit $record) {
                            $shipment = $record->shipment;
                            if (! $shipment || ! ShipmentOwnership::canEdit(Filament::auth()->user(), $shipment)) {
                                return false;
                            }
                            if ($shipment->latest_track_status?->value !== TrackStatus::Stacking->value) {
                                return false;
                            }

                            return ! LoadingSessionAutoCreate::isRackShipment($shipment);
                        })
                        ->action(function (Unit $record, array $data) {
                            $shipment = $record->shipment;
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $shipment), 403);
                            $shipment->appendTrack(TrackStatus::UnitLoading, $data['note'] ?? null);
                            Notification::make()->title('Dimuat di Kapal dicatat')->success()->send();
                        }),

                    Action::make('unitLoadingInfo')
                        ->label('Loading via AppSheet')
                        ->icon('heroicon-m-device-phone-mobile')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Loading Checkpoint — Via AppSheet')
                        ->modalDescription('Untuk shipment ber-rack, status "Dimuat di Kapal" diupdate otomatis setelah loading checkpoint selesai di AppSheet.')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup')
                        ->visible(function (Unit $record) {
                            $shipment = $record->shipment;
                            if (! $shipment || $shipment->latest_track_status?->value !== TrackStatus::Stacking->value) {
                                return false;
                            }

                            return LoadingSessionAutoCreate::isRackShipment($shipment);
                        })
                        ->action(fn() => null),

                    Action::make('onShip')
                        ->label('On Ship')
                        ->icon('heroicon-m-rocket-launch')
                        ->color('info')
                        ->visible(
                            fn(Unit $record) => $record->shipment?->latest_track_status?->value === TrackStatus::UnitLoading->value
                                && ShipmentOwnership::canEdit(Filament::auth()->user(), $record->shipment)
                        )
                        ->form([Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Unit $record, array $data) {
                            $shipment = $record->shipment;
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $shipment), 403);
                            $shipment->appendTrack(TrackStatus::OnShip, $data['note'] ?? null);
                            Notification::make()->title('On Ship dicatat')->success()->send();
                        }),

                    Action::make('vesselDepart')
                        ->label('Kapal Berangkat')
                        ->icon('heroicon-m-paper-airplane')
                        ->color('info')
                        ->visible(
                            fn(Unit $record) => $record->shipment?->latest_track_status?->value === TrackStatus::OnShip->value
                                && ShipmentOwnership::canEdit(Filament::auth()->user(), $record->shipment)
                        )
                        ->form([Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Unit $record, array $data) {
                            $shipment = $record->shipment;
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $shipment), 403);
                            $shipment->appendTrack(TrackStatus::VesselDepart, $data['note'] ?? null);
                            Notification::make()->title('Kapal Berangkat dicatat')->success()->send();
                        }),

                    Action::make('vesselArrival')
                        ->label('Kapal Tiba')
                        ->icon('heroicon-m-flag')
                        ->color('info')
                        ->visible(
                            fn(Unit $record) => $record->shipment?->latest_track_status?->value === TrackStatus::VesselDepart->value
                                && ShipmentOwnership::canEdit(Filament::auth()->user(), $record->shipment)
                        )
                        ->form([Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Unit $record, array $data) {
                            $shipment = $record->shipment;
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $shipment), 403);
                            $shipment->appendTrack(TrackStatus::VesselArrival, $data['note'] ?? null);
                            Notification::make()->title('Kapal Tiba dicatat')->success()->send();
                        }),

                    Action::make('unloading')
                        ->label('Pembongkaran')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->color('info')
                        ->visible(
                            fn(Unit $record) => $record->shipment?->latest_track_status?->value === TrackStatus::VesselArrival->value
                                && ShipmentOwnership::canEdit(Filament::auth()->user(), $record->shipment)
                        )
                        ->form([Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Unit $record, array $data) {
                            $shipment = $record->shipment;
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $shipment), 403);
                            try {
                                $shipment->appendTrack(TrackStatus::Unloading, $data['note'] ?? null);
                                Notification::make()->title('Pembongkaran dicatat')->success()->send();
                            } catch (DomainException $e) {
                                Notification::make()->title($e->getMessage())->danger()->send();
                            }
                        }),
                    Action::make('handoverTrucking')
                        ->label('Handover Selfdrive')
                        ->icon('heroicon-m-arrow-trending-up')
                        ->color('info')
                        ->visible(
                            fn(Unit $record) => $record->shipment?->nextTrackStatus() === TrackStatus::HandoverTrucking
                                && ShipmentOwnership::canEdit(Filament::auth()->user(), $record->shipment)
                        )
                        ->form([Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Unit $record, array $data) {
                            $shipment = $record->shipment;
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $shipment), 403);
                            $shipment->appendTrack(TrackStatus::HandoverTrucking, $data['note'] ?? null);
                            Notification::make()->title('Handover Selfdrive dicatat')->success()->send();
                        }),

                    Action::make('deliveryToCustomer')
                        ->label('Antar ke Customer')
                        ->icon('heroicon-m-user')
                        ->color('info')
                        ->visible(
                            fn(Unit $record) => $record->shipment?->nextTrackStatus() === TrackStatus::DeliveryToCustomer
                                && ShipmentOwnership::canEdit(Filament::auth()->user(), $record->shipment)
                        )
                        ->form([Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Unit $record, array $data) {
                            $shipment = $record->shipment;
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $shipment), 403);
                            try {
                                $shipment->appendTrack(TrackStatus::DeliveryToCustomer, $data['note'] ?? null);
                                Notification::make()->title('Antar ke Customer dicatat')->success()->send();
                            } catch (DomainException $e) {
                                Notification::make()->title($e->getMessage())->danger()->send();
                            }
                        }),

                    Action::make('markDelivered')
                        ->label('Tandai Terkirim')
                        ->icon('heroicon-m-check-badge')
                        ->color('success')
                        ->visible(
                            fn(Unit $record) => $record->shipment?->latest_track_status?->value === TrackStatus::DeliveryToCustomer->value
                                && ShipmentOwnership::canEdit(Filament::auth()->user(), $record->shipment)
                        )
                        ->form([Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Unit $record, array $data) {
                            $shipment = $record->shipment;
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $shipment), 403);
                            $shipment->appendTrack(TrackStatus::Delivered, $data['note'] ?? 'Terkirim');
                            Notification::make()->title('Shipment terkirim!')->success()->send();
                        }),

                    Action::make('hold')
                        ->label('Tahan')
                        ->icon('heroicon-m-pause-circle')
                        ->color('warning')
                        ->visible(
                            fn(Unit $record) => ShipmentOwnership::canEdit(Filament::auth()->user(), $record->shipment)
                                && $record->shipment?->latest_track_status !== TrackStatus::Hold
                                && ! in_array($record->shipment?->latest_track_status, [TrackStatus::Delivered, TrackStatus::Cancelled], true)
                                && $record->shipment?->latest_track_status !== null
                        )
                        ->form([Textarea::make('note')->label('Alasan')->rows(3)->required()])
                        ->action(function (Unit $record, array $data) {
                            $shipment = $record->shipment;
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $shipment), 403);
                            $shipment->appendTrack(TrackStatus::Hold, $data['note']);
                            Notification::make()->title('Shipment ditahan')->warning()->send();
                        }),

                    Action::make('cancel')
                        ->label('Batalkan')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->visible(
                            fn(Unit $record) => $record->shipment?->canCancel()
                                && ShipmentOwnership::canEdit(Filament::auth()->user(), $record->shipment)
                        )
                        ->form([Textarea::make('note')->label('Alasan')->rows(3)->required()])
                        ->requiresConfirmation()
                        ->action(function (Unit $record, array $data) {
                            $shipment = $record->shipment;
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $shipment), 403);
                            $shipment->appendTrack(TrackStatus::Cancelled, $data['note']);
                            $shipment->forceFill([
                                'cancelled_at' => now(),
                                'cancelled_by' => Filament::auth()->id(),
                            ])->save();
                            Notification::make()->title('Shipment dibatalkan')->danger()->send();
                        }),

                ])->label('Aksi Status')->icon('heroicon-m-cog')->color('gray'),

                // ── Detail & Cetak ────────────────────────────────────────────
                ActionGroup::make([
                    Action::make('viewDetail')
                        ->label('Lihat Detail')
                        ->icon('heroicon-m-eye')
                        ->color('gray')
                        ->url(
                            fn(Unit $record): string => OperationalShipmentPage::getUrl(['record' => $record->shipment_id])
                        )
                        ->openUrlInNewTab(),

                    Action::make('printWaybill')
                        ->label('Cetak Waybill')
                        ->icon('heroicon-m-printer')
                        ->color('primary')
                        ->url(fn(Unit $record): string => route('shipments.print.waybill', $record->shipment_id))
                        ->openUrlInNewTab()
                        ->visible(fn(Unit $record) => auth()->user()?->can('print', $record->shipment)),

                    Action::make('printPackingList')
                        ->label('Cetak Packing List')
                        ->icon('heroicon-m-clipboard-document-list')
                        ->color('info')
                        ->url(fn(Unit $record): string => route('shipments.print.packing', $record->shipment_id))
                        ->openUrlInNewTab()
                        ->visible(fn(Unit $record) => auth()->user()?->can('print', $record->shipment)),

                    Action::make('printResi')
                        ->label('Cetak Resi')
                        ->icon('heroicon-m-document-text')
                        ->color('gray')
                        ->url(fn(Unit $record): string => route('shipments.resi', $record->shipment_id))
                        ->openUrlInNewTab()
                        ->visible(fn(Unit $record) => auth()->user()?->can('print', $record->shipment)),
                ])->label('Lainnya')->icon('heroicon-m-ellipsis-vertical')->color('gray'),

            ])
            ->searchable()
            ->striped()
            ->paginated([15, 25, 50])
            ->emptyStateHeading('Tidak ada pekerjaan aktif')
            ->emptyStateDescription('Semua pekerjaan sudah selesai atau belum ada shipment yang ditugaskan.')
            ->emptyStateIcon('heroicon-o-inbox');
    }
}
