<?php

namespace App\Filament\FC\Pages;

use App\Enums\MPCheckStatus;
use App\Enums\ShipmentStatus;
use App\Enums\TrackStatus;
use App\Filament\FC\Pages\OperationalShipmentPage;
use App\Filament\FC\Resources\BriefingSessionResource;
use App\Filament\FC\Resources\ContainerReadinessSessionResource;
use App\Filament\FC\Resources\ShipmentResource;
use App\Models\BriefingSession;
use App\Models\Depot;
use App\Models\Shipment;
use Illuminate\Support\Carbon;
use App\Models\UnitInspection;
use App\Models\UnitInspectionItem;
use App\Services\InspectionDraftAutoCreate;
use App\Services\InspectionGateEvaluator;
use App\Services\LoadingSessionAutoCreate;
use App\Services\ShipmentOperationalGateResolver;
use App\Services\ShipmentOwnership;
use DomainException;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class OperationalTasks extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationGroup = 'Operasional Lapangan';
    protected static ?string $navigationLabel = 'Tugas Operasional';
    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-document-list';
    protected static ?int    $navigationSort  = 10;

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
        return auth()->user()?->hasRole('field_coordinator') ?? false;
    }

    // ── Scope resolution ──────────────────────────────────────────────────────

    private function depotId(): ?int
    {
        return app()->bound('scope.depot_id') ? (int) app('scope.depot_id') : null;
    }

    private function portId(): ?int
    {
        $depot = $this->depotId();

        return $depot ? Depot::whereKey($depot)->value('port_id') : null;
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
                'exists'       => $briefing !== null,
                'status_label' => $briefingStatusLabel,
                'create_url'   => BriefingSessionResource::getUrl('create'),
                'view_url'     => $briefing
                    ? BriefingSessionResource::getUrl('view', ['record' => $briefing->id])
                    : null,
            ],
            'container' => [
                'exists'     => $container !== null,
                'is_ready'   => $container ? (bool) $container->summary_sufficient : null,
                'create_url' => ContainerReadinessSessionResource::getUrl('create'),
                'edit_url'   => $container
                    ? ContainerReadinessSessionResource::getUrl('edit', ['record' => $container->id])
                    : null,
            ],
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

    // ── Query ─────────────────────────────────────────────────────────────────

    protected function getTableQuery(): Builder
    {
        $depotId = $this->depotId();
        $portId  = $this->portId();
        $userId  = auth()->id();

        return Shipment::query()
            ->select('shipments.*')
            ->addSelect($this->unitStatusSubqueries())
            ->where('mode', 'sea')
            ->whereNotIn('status', ['draft', 'delivered', 'cancelled'])
            ->with($this->eagerLoads())
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
                                fn(Builder $t) =>
                                $t->whereIn('status', self::originStatuses())
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
                                    fn($v) =>
                                    $v->from('voyages')
                                        ->whereColumn('voyages.id', 'shipments.voyage_id')
                                        ->where('voyages.pod_id', $portId)
                                );
                        })->whereHas(
                            'latestTrack',
                            fn(Builder $t) =>
                            $t->whereIn('status', self::destActiveStatuses())
                        );
                    });
                }

                // ARM C — hold: visible to any FC with ownership over this shipment.
                //   Phase-gated actions (canEdit) still block unauthorized mutations.
                $outer->orWhere(function (Builder $c) use ($depotId, $portId, $userId) {
                    $c->whereHas(
                        'latestTrack',
                        fn(Builder $t) =>
                        $t->where('status', TrackStatus::Hold->value)
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
                                        fn($v) =>
                                        $v->from('voyages')
                                            ->whereColumn('voyages.id', 'shipments.voyage_id')
                                            ->where('voyages.pod_id', $portId)
                                    );
                            });
                        }
                    });
                });
            })
            ->orderByDesc(
                DB::raw("(SELECT tracked_at FROM shipment_tracks
                          WHERE shipment_id = shipments.id
                            AND tracked_at IS NOT NULL
                          ORDER BY tracked_at DESC LIMIT 1)")
            );
    }

    // ── Eager loads ───────────────────────────────────────────────────────────

    private function eagerLoads(): array
    {
        return [
            'voyage:id,voyage_no,eta',
            'voyage.vessel:id,name',
            'units:id,shipment_id',
            'latestTrack',
            'customer:id,name',
        ];
    }

    // ── Unit-status subqueries (SC.5D.10A) ────────────────────────────────────

    private function unitStatusSubqueries(): array
    {
        $exitGate = "EXISTS (
            SELECT 1 FROM shipment_tracks st_exit
            WHERE st_exit.shipment_id = shipments.id
              AND st_exit.tracked_at IS NOT NULL
              AND (
                  (shipments.mode IN ('sea', 'sea_freight')
                   AND shipments.vehicle_loading IN ('rack', 'flat_rack')
                   AND st_exit.status = 'delivery_to_port')
                  OR (NOT (shipments.mode IN ('sea', 'sea_freight')
                           AND shipments.vehicle_loading IN ('rack', 'flat_rack'))
                      AND st_exit.status = 'stuffing')
              )
        )";

        $waitingSql = "(
            SELECT COUNT(u.id)
            FROM units u
            WHERE u.shipment_id = shipments.id
              AND EXISTS (
                  SELECT 1 FROM shipment_tracks st_h
                  WHERE st_h.shipment_id = shipments.id
                    AND st_h.status = 'handover'
                    AND st_h.tracked_at IS NOT NULL
              )
              AND NOT EXISTS (
                  SELECT 1 FROM unit_inspections ui
                  WHERE ui.unit_id = u.id
                    AND ui.stage = 'handover_depot'
                    AND ui.submitted_at IS NOT NULL
              )
              AND NOT {$exitGate}
        )";

        // Uses latest inspection per unit (MAX(id)) to match buildBermasalahTable() semantics.
        $bermasalahSql = "(
            SELECT COUNT(u.id)
            FROM units u
            WHERE u.shipment_id = shipments.id
              AND EXISTS (
                  SELECT 1 FROM unit_inspections ui
                  WHERE ui.unit_id = u.id
                    AND ui.stage = 'handover_depot'
                    AND ui.gate_decision = 'return_to_pdc'
                    AND ui.id = (
                        SELECT MAX(ui2.id)
                        FROM unit_inspections ui2
                        WHERE ui2.unit_id = u.id
                          AND ui2.stage = 'handover_depot'
                    )
              )
              AND NOT {$exitGate}
        )";

        $readinessSql = "(
            SELECT CASE
                WHEN COUNT(u.id) = 0 THEN NULL
                ELSE ROUND(
                    100.0
                    * SUM(CASE WHEN EXISTS (
                        SELECT 1 FROM unit_inspections ui
                        WHERE ui.unit_id = u.id
                          AND ui.stage = 'handover_depot'
                          AND ui.submitted_at IS NOT NULL
                          AND ui.gate_decision IN ('accept', 'allow_with_remark')
                    ) THEN 1 ELSE 0 END)
                    / COUNT(u.id)
                )
            END
            FROM units u
            WHERE u.shipment_id = shipments.id
        )::integer";

        return [
            DB::raw("{$waitingSql} AS waiting_inspection_count"),
            DB::raw("{$bermasalahSql} AS bermasalah_count"),
            DB::raw("{$readinessSql} AS readiness_pct"),
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
                        fn(Shipment $record): string =>
                        ShipmentOperationalGateResolver::resolve($record) === ShipmentOperationalGateResolver::DESTINATION
                            ? 'TUJUAN'
                            : 'ASAL'
                    )
                    ->color(fn(string $state): string => $state === 'TUJUAN' ? 'info' : 'warning'),

                TextColumn::make('code')
                    ->label('Shipment')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->url(
                        fn(Shipment $record): string =>
                        OperationalShipmentPage::getUrl(['record' => $record->getKey()])
                    )
                    ->openUrlInNewTab(),

                TextColumn::make('customer.name')
                    ->label('Pengirim')
                    ->default('—')
                    ->searchable(),

                TextColumn::make('latestTrack.status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn($state): string =>
                        $state instanceof TrackStatus
                            ? $state->label()
                            : (TrackStatus::tryFrom((string) $state)?->label() ?? ((string) $state ?: 'Pending'))
                    )
                    ->color(fn($state): string => match ((string) ($state instanceof TrackStatus ? $state->value : ($state ?? ''))) {
                        TrackStatus::Pickup->value             => 'gray',
                        TrackStatus::Handover->value           => 'gray',
                        TrackStatus::Stuffing->value,
                        TrackStatus::DeliveryToPort->value,
                        TrackStatus::Stacking->value,
                        TrackStatus::UnitLoading->value        => 'warning',
                        TrackStatus::OnShip->value,
                        TrackStatus::VesselDepart->value       => 'info',
                        TrackStatus::VesselArrival->value      => 'primary',
                        TrackStatus::Unloading->value          => 'warning',
                        TrackStatus::HandoverTrucking->value   => 'primary',
                        TrackStatus::DeliveryToCustomer->value => 'success',
                        TrackStatus::Delivered->value          => 'success',
                        TrackStatus::Hold->value               => 'danger',
                        TrackStatus::Cancelled->value          => 'danger',
                        default                                => 'gray',
                    }),

                TextColumn::make('tahap_operasional')
                    ->label('Tahap')
                    ->badge()
                    ->getStateUsing(function (Shipment $record): string {
                        $v = $record->latest_track_status?->value ?? '';
                        return match ($v) {
                            TrackStatus::Pickup->value             => 'Pickup',
                            TrackStatus::Handover->value           => 'Handover',
                            TrackStatus::Stuffing->value           => 'Stuffing',
                            TrackStatus::DeliveryToPort->value     => 'Port Delivery',
                            TrackStatus::Stacking->value           => 'Stacking',
                            TrackStatus::UnitLoading->value        => 'Loading',
                            TrackStatus::OnShip->value             => 'On Ship',
                            TrackStatus::VesselDepart->value       => 'Berangkat',
                            TrackStatus::VesselArrival->value      => 'Arrival',
                            TrackStatus::Unloading->value          => 'Unloading',
                            TrackStatus::HandoverTrucking->value   => 'Selfdrive',
                            TrackStatus::DeliveryToCustomer->value => 'Delivery',
                            TrackStatus::Hold->value               => 'Ditahan',
                            default                                => 'Menunggu',
                        };
                    })
                    ->color(function (Shipment $record): string {
                        $v = $record->latest_track_status?->value ?? '';
                        return match ($v) {
                            TrackStatus::Pickup->value,
                            TrackStatus::Handover->value           => 'gray',
                            TrackStatus::Stuffing->value,
                            TrackStatus::DeliveryToPort->value,
                            TrackStatus::Stacking->value,
                            TrackStatus::UnitLoading->value        => 'warning',
                            TrackStatus::OnShip->value,
                            TrackStatus::VesselDepart->value       => 'info',
                            TrackStatus::VesselArrival->value,
                            TrackStatus::Unloading->value          => 'primary',
                            TrackStatus::HandoverTrucking->value,
                            TrackStatus::DeliveryToCustomer->value => 'success',
                            TrackStatus::Hold->value               => 'danger',
                            default                                => 'gray',
                        };
                    }),

                TextColumn::make('waiting_inspection_count')
                    ->label('Menunggu Inspeksi')
                    ->badge()
                    ->getStateUsing(fn(Shipment $record): int => (int) ($record->waiting_inspection_count ?? 0))
                    ->color(fn(Shipment $record): string => match (true) {
                        ((int) ($record->waiting_inspection_count ?? 0)) === 0 => 'success',
                        ((int) ($record->waiting_inspection_count ?? 0)) <= 2  => 'warning',
                        default                                                 => 'danger',
                    })
                    ->sortable(query: fn(Builder $query, string $direction): Builder =>
                        $query->reorder()->orderBy('waiting_inspection_count', $direction)
                    ),

                TextColumn::make('bermasalah_count')
                    ->label('Unit Bermasalah')
                    ->badge()
                    ->getStateUsing(fn(Shipment $record): int => (int) ($record->bermasalah_count ?? 0))
                    ->color(fn(Shipment $record): string =>
                        ((int) ($record->bermasalah_count ?? 0)) === 0 ? 'success' : 'danger'
                    )
                    ->sortable(query: fn(Builder $query, string $direction): Builder =>
                        $query->reorder()->orderBy('bermasalah_count', $direction)
                    ),

                TextColumn::make('readiness_pct')
                    ->label('Readiness')
                    ->badge()
                    ->getStateUsing(function (Shipment $record): string {
                        $pct = $record->readiness_pct;
                        return $pct === null ? '—' : "{$pct}%";
                    })
                    ->color(function (Shipment $record): string {
                        $pct = $record->readiness_pct;
                        if ($pct === null) return 'gray';
                        if ($pct >= 100) return 'success';
                        if ($pct >= 60) return 'warning';
                        return 'danger';
                    })
                    ->sortable(query: fn(Builder $query, string $direction): Builder =>
                        $query->reorder()->orderBy('readiness_pct', $direction)
                    ),

                TextColumn::make('voyage_no_display')
                    ->label('Voyage')
                    ->getStateUsing(
                        fn(Shipment $record): string =>
                        $record->getRelation('voyage')?->voyage_no ?? '—'
                    )
                    ->badge()
                    ->color('gray'),

                TextColumn::make('voyage_eta_display')
                    ->label('ETA')
                    ->getStateUsing(
                        fn(Shipment $record): string => blank($record->getRelation('voyage')?->eta)
                            ? '—'
                            : \Carbon\Carbon::parse($record->getRelation('voyage')->eta)->format('d M Y')
                    ),

                TextColumn::make('units_count')
                    ->label('Unit')
                    ->getStateUsing(fn(Shipment $record): int => $record->getRelation('units')?->count() ?? 0)
                    ->alignCenter(),

                TextColumn::make('latestTrack.tracked_at')
                    ->label('Diperbarui')
                    ->formatStateUsing(
                        fn($state): string => blank($state)
                            ? '—'
                            : \Carbon\Carbon::parse($state)->format('d M Y H:i')
                    )
                    ->sortable(),
            ])
            ->actions([

                // ── Update Lapangan + Inspeksi Unit (workspace tunggal) ──────
                Action::make('updateTrack')
                    ->label('Update')
                    ->icon('heroicon-m-pencil-square')
                    ->color('info')
                    ->visible(fn(Shipment $record) => ShipmentOwnership::canEdit(Filament::auth()->user(), $record))
                    ->form(fn(Shipment $record) => array_merge(
                        ShipmentResource::trackUpdateForm(),
                        ShipmentResource::inspectionFormFields(),
                    ))
                    ->fillForm(function (Shipment $record): array {
                        $nextStatus = $record->nextTrackStatus();
                        $stage      = $nextStatus ? InspectionDraftAutoCreate::resolveStage($nextStatus) : null;

                        $data = [
                            'track_status'     => $nextStatus?->value,
                            'inspection_stage' => $stage,
                        ];

                        if ($stage && $nextStatus) {
                            // Ensure draft inspection records exist for each unit.
                            // ensureForShipmentAndStage() is idempotent (firstOrCreate) and handles
                            // the pickup case where no skeleton track exists yet.
                            try {
                                InspectionDraftAutoCreate::ensureForShipmentAndStage($record, $stage);
                            } catch (\Throwable $e) {
                                \Illuminate\Support\Facades\Log::error('FC inspection draft generation failed', [
                                    'shipment_id' => $record->id,
                                    'stage'       => $stage,
                                    'error'       => $e->getMessage(),
                                ]);
                            }

                            $units = $record->units()->with([
                                'inspections' => fn($q) => $q->where('stage', $stage)->with('items'),
                            ])->get();

                            // Flat list — avoids Filament v3 nested Repeater hydration bug
                            $flatItems = [];
                            foreach ($units as $unit) {
                                $inspection = $unit->inspections->first();
                                $unitLabel  = trim(implode(' · ', array_filter([$unit->model_no, $unit->chassis_no])))
                                    ?: 'Unit #' . $unit->id;
                                foreach (($inspection?->items ?? collect()) as $item) {
                                    $flatItems[] = [
                                        'item_id'            => $item->id,
                                        'inspection_id'      => $inspection->id,
                                        'unit_id'            => $unit->id,
                                        'unit_label'         => $unitLabel,
                                        'unit_label_display' => $unitLabel,
                                        'category'           => $item->category,
                                        'item_name'          => $item->item_name,
                                        'result'             => $item->result ?? UnitInspectionItem::RESULT_OK,
                                        'finding_type'       => $item->finding_type,
                                        'notes'              => $item->notes,
                                    ];
                                }
                            }
                            $data['inspection_items_flat'] = $flatItems;
                        }

                        return $data;
                    })
                    ->action(function (Shipment $record, array $data, $livewire) {
                        abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $record), 403);
                        $status = TrackStatus::from($data['track_status']);

                        $existing = $record->tracks()
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

                        if ($status === TrackStatus::UnitLoading && LoadingSessionAutoCreate::isRackShipment($record)) {
                            Notification::make()
                                ->title('Update otomatis via AppSheet')
                                ->body('Status "Dimuat di Kapal" diupdate otomatis setelah loading checkpoint selesai.')
                                ->warning()
                                ->send();
                            return;
                        }

                        // ── Save inspection data & evaluate gate ──────────────
                        $inspStage = $data['inspection_stage'] ?? null;
                        $flatItems = $data['inspection_items_flat'] ?? [];
                        $checkRefs = [];

                        if ($inspStage && ! empty($flatItems)) {
                            // Group flat rows by inspection_id so we evaluate gate once per inspection
                            $grouped = collect($flatItems)->groupBy('inspection_id');

                            foreach ($grouped as $inspId => $rows) {
                                if (! $inspId) {
                                    continue;
                                }

                                $inspection = UnitInspection::find($inspId);
                                if (! $inspection) {
                                    continue;
                                }

                                $unitId    = $rows->first()['unit_id'] ?? null;
                                $unitLabel = $rows->first()['unit_label'] ?? ('Unit #' . $unitId);

                                // Persist each item result
                                foreach ($rows as $itemData) {
                                    $itemId = $itemData['item_id'] ?? null;
                                    if (! $itemId) {
                                        continue;
                                    }
                                    $isNg = ($itemData['result'] ?? '') === UnitInspectionItem::RESULT_NG;
                                    UnitInspectionItem::where('id', $itemId)->update([
                                        'result'       => $itemData['result'] ?? UnitInspectionItem::RESULT_OK,
                                        'finding_type' => $isNg ? ($itemData['finding_type'] ?? null) : null,
                                        'notes'        => $isNg ? ($itemData['notes'] ?? null) : null,
                                    ]);
                                }

                                // Evaluate gate decision
                                $inspection->refresh();
                                $gateDecision = app(InspectionGateEvaluator::class)->evaluate($inspection);
                                $hasNg        = $inspection->items()
                                    ->where('result', UnitInspectionItem::RESULT_NG)
                                    ->exists();

                                $inspection->update([
                                    'submitted_at'  => now(),
                                    'checked_at'    => now(),
                                    'checked_by'    => auth()->id(),
                                    'status'        => $hasNg
                                        ? UnitInspection::STATUS_FAILED
                                        : UnitInspection::STATUS_PASSED,
                                    'gate_decision' => $gateDecision,
                                ]);

                                $checkRefs[] = [
                                    'unit_id'       => $unitId,
                                    'inspection_id' => $inspection->id,
                                    'stage'         => $inspStage,
                                    'status'        => $inspection->status,
                                    'gate_decision' => $gateDecision,
                                ];

                                if ($gateDecision === UnitInspection::GATE_RETURN_TO_PDC) {
                                    Notification::make()
                                        ->title('Gate Decision: Return to PDC')
                                        ->body("Unit {$unitLabel} memiliki kerusakan fisik. Track status tidak dapat dilanjutkan.")
                                        ->danger()
                                        ->send();
                                    return;
                                }
                            }
                        }

                        $override = null;
                        if (auth_user()?->hasRole('super_admin') && ! empty($data['override_reason'])) {
                            $override = ['reason' => $data['override_reason']];
                        }

                        try {
                            $savedTrack = $record->appendTrack(
                                $status,
                                $data['note'] ?? null,
                                null,
                                null,
                                $override,
                                $data['checkseet'] ?? null,
                                $data['plan_loading_time_at'] ?? null,
                                $data['plan_closing_time_at'] ?? null,
                            );

                            // Store inspection refs in check_result for ViewShipment timeline
                            if (! empty($checkRefs)) {
                                $savedTrack->updateQuietly([
                                    'check_result' => ['unit_inspections' => $checkRefs],
                                ]);
                            }

                            Notification::make()
                                ->title('Update lapangan tersimpan')
                                ->body("Status: {$status->label()}")
                                ->success()
                                ->send();

                            if ($status === TrackStatus::Pickup) {
                                $livewire->redirect(OperationalShipmentPage::getUrl(['record' => $record->getKey()]));
                            }
                        } catch (DomainException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),

                // ── Aksi cepat per status ─────────────────────────────────────
                ActionGroup::make([

                    Action::make('toPending')
                        ->label('Set Menunggu')
                        ->icon('heroicon-m-clock')
                        ->color('gray')
                        ->visible(fn(Shipment $record) => in_array(
                            $record->status?->value ?? (string) $record->status,
                            ['draft', 'hold'],
                            true
                        ))
                        ->requiresConfirmation()
                        ->action(function (Shipment $record) {
                            $record->update(['status' => ShipmentStatus::Pending->value]);
                            Notification::make()->title('Status di-set ke Menunggu')->success()->send();
                        }),

                    Action::make('startPickup')
                        ->label('Mulai Penjemputan')
                        ->icon('heroicon-m-truck')
                        ->color('info')
                        ->visible(
                            fn(Shipment $record) => ($record->status?->value ?? (string) $record->status) === 'pending'
                                && ShipmentOwnership::canEdit(Filament::auth()->user(), $record)
                        )
                        ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Shipment $record, array $data, $livewire) {
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $record), 403);
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
                                ->body('Lakukan inspeksi pickup untuk setiap unit di halaman detail.')
                                ->success()
                                ->send();
                            $livewire->redirect(OperationalShipmentPage::getUrl(['record' => $record->getKey()]));
                        }),

                    Action::make('handover')
                        ->label('Handover Depo')
                        ->icon('heroicon-m-building-office')
                        ->color('info')
                        ->visible(
                            fn(Shipment $record) =>
                            $record->latest_track_status?->value === TrackStatus::Pickup->value
                                && ShipmentOwnership::canEdit(Filament::auth()->user(), $record)
                        )
                        ->form([
                            \Filament\Forms\Components\TextInput::make('sjkb_no')
                                ->label('Nomor SJKB')
                                ->required()
                                ->maxLength(120),
                            \Filament\Forms\Components\TextInput::make('yard_slot')
                                ->label('Yard Slot')
                                ->placeholder('A-01 / B-03')
                                ->maxLength(50),
                            \Filament\Forms\Components\Textarea::make('note')
                                ->label('Catatan')
                                ->rows(3),
                        ])
                        ->action(function (Shipment $record, array $data, $livewire) {
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $record), 403);
                            try {
                                foreach ($record->units as $unit) {
                                    $unit->update(['sjkb_no' => $data['sjkb_no']]);
                                }

                                $record->appendTrack(
                                    TrackStatus::Handover,
                                    $data['note'] ?? null,
                                    $data['yard_slot'] ?? null,
                                );

                                Notification::make()
                                    ->title('Handover Depo dicatat')
                                    ->body('Selesaikan inspeksi unit sebelum proses stuffing.')
                                    ->success()
                                    ->send();

                                $livewire->redirect(OperationalShipmentPage::getUrl(['record' => $record->getKey()]));
                            } catch (\DomainException $e) {
                                Notification::make()->title($e->getMessage())->danger()->send();
                            }
                        }),

                    Action::make('stuffing')
                        ->label('Stuffing & Segel')
                        ->icon('heroicon-m-wrench-screwdriver')
                        ->color('info')
                        ->visible(function (Shipment $record) {
                            if (! ShipmentOwnership::canEdit(Filament::auth()->user(), $record)) return false;
                            if ($record->latest_track_status?->value !== TrackStatus::Handover->value) return false;
                            if (((int) ($record->waiting_inspection_count ?? 0)) > 0) return false;
                            if (((int) ($record->bermasalah_count ?? 0)) > 0) return false;
                            return ! LoadingSessionAutoCreate::isRackShipment($record);
                        })
                        ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Shipment $record, array $data) {
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $record), 403);
                            $record->appendTrack(TrackStatus::Stuffing, $data['note'] ?? null);
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
                        ->visible(function (Shipment $record) {
                            if ($record->latest_track_status?->value !== TrackStatus::Handover->value) return false;
                            if (((int) ($record->waiting_inspection_count ?? 0)) > 0) return false;
                            if (((int) ($record->bermasalah_count ?? 0)) > 0) return false;
                            return LoadingSessionAutoCreate::isRackShipment($record);
                        })
                        ->action(fn() => null),

                    Action::make('deliveryToPort')
                        ->label('Antar ke Pelabuhan')
                        ->icon('heroicon-m-arrow-up-right')
                        ->color('info')
                        ->visible(function (Shipment $record) {
                            if (! ShipmentOwnership::canEdit(Filament::auth()->user(), $record)) return false;
                            $last = $record->latest_track_status?->value;
                            if ($last === TrackStatus::Stuffing->value) return true;
                            if ($last === TrackStatus::Handover->value && LoadingSessionAutoCreate::isRackShipment($record)) {
                                if (((int) ($record->waiting_inspection_count ?? 0)) > 0) return false;
                                if (((int) ($record->bermasalah_count ?? 0)) > 0) return false;
                                return true;
                            }
                            return false;
                        })
                        ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Shipment $record, array $data) {
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $record), 403);
                            $record->appendTrack(TrackStatus::DeliveryToPort, $data['note'] ?? null);
                            Notification::make()->title('Antar ke Pelabuhan dicatat')->success()->send();
                        }),

                    Action::make('stacking')
                        ->label('Stacking (Terminal)')
                        ->icon('heroicon-m-rectangle-group')
                        ->color('info')
                        ->visible(
                            fn(Shipment $record) =>
                            $record->latest_track_status?->value === TrackStatus::DeliveryToPort->value
                                && ShipmentOwnership::canEdit(Filament::auth()->user(), $record)
                        )
                        ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Shipment $record, array $data) {
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $record), 403);
                            $record->appendTrack(TrackStatus::Stacking, $data['note'] ?? null);
                            Notification::make()->title('Stacking dicatat')->success()->send();
                        }),

                    Action::make('unitLoadingAuto')
                        ->label('Dimuat di Kapal')
                        ->icon('heroicon-m-arrow-path')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(function (Shipment $record) {
                            if (! ShipmentOwnership::canEdit(Filament::auth()->user(), $record)) return false;
                            if ($record->latest_track_status?->value !== TrackStatus::Stacking->value) return false;
                            return ! LoadingSessionAutoCreate::isRackShipment($record);
                        })
                        ->action(function (Shipment $record, array $data) {
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $record), 403);
                            $record->appendTrack(TrackStatus::UnitLoading, $data['note'] ?? null);
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
                        ->visible(function (Shipment $record) {
                            if ($record->latest_track_status?->value !== TrackStatus::Stacking->value) return false;
                            return LoadingSessionAutoCreate::isRackShipment($record);
                        })
                        ->action(fn() => null),

                    Action::make('onShip')
                        ->label('On Ship')
                        ->icon('heroicon-m-rocket-launch')
                        ->color('info')
                        ->visible(
                            fn(Shipment $record) =>
                            $record->latest_track_status?->value === TrackStatus::UnitLoading->value
                                && ShipmentOwnership::canEdit(Filament::auth()->user(), $record)
                        )
                        ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Shipment $record, array $data) {
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $record), 403);
                            $record->appendTrack(TrackStatus::OnShip, $data['note'] ?? null);
                            Notification::make()->title('On Ship dicatat')->success()->send();
                        }),

                    Action::make('vesselDepart')
                        ->label('Kapal Berangkat')
                        ->icon('heroicon-m-paper-airplane')
                        ->color('info')
                        ->visible(
                            fn(Shipment $record) =>
                            $record->latest_track_status?->value === TrackStatus::OnShip->value
                                && ShipmentOwnership::canEdit(Filament::auth()->user(), $record)
                        )
                        ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Shipment $record, array $data) {
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $record), 403);
                            $record->appendTrack(TrackStatus::VesselDepart, $data['note'] ?? null);
                            Notification::make()->title('Kapal Berangkat dicatat')->success()->send();
                        }),

                    Action::make('vesselArrival')
                        ->label('Kapal Tiba')
                        ->icon('heroicon-m-flag')
                        ->color('info')
                        ->visible(
                            fn(Shipment $record) =>
                            $record->latest_track_status?->value === TrackStatus::VesselDepart->value
                                && ShipmentOwnership::canEdit(Filament::auth()->user(), $record)
                        )
                        ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Shipment $record, array $data) {
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $record), 403);
                            $record->appendTrack(TrackStatus::VesselArrival, $data['note'] ?? null);
                            Notification::make()->title('Kapal Tiba dicatat')->success()->send();
                        }),

                    Action::make('unloading')
                        ->label('Pembongkaran')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->color('info')
                        ->visible(
                            fn(Shipment $record) =>
                            $record->latest_track_status?->value === TrackStatus::VesselArrival->value
                                && ShipmentOwnership::canEdit(Filament::auth()->user(), $record)
                        )
                        ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Shipment $record, array $data) {
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $record), 403);
                            try {
                                $record->appendTrack(TrackStatus::Unloading, $data['note'] ?? null);
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
                            fn(Shipment $record) =>
                            $record->latest_track_status?->value === TrackStatus::Unloading->value
                                && ShipmentOwnership::canEdit(Filament::auth()->user(), $record)
                        )
                        ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Shipment $record, array $data) {
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $record), 403);
                            $record->appendTrack(TrackStatus::HandoverTrucking, $data['note'] ?? null);
                            Notification::make()->title('Handover Selfdrive dicatat')->success()->send();
                        }),

                    Action::make('deliveryToCustomer')
                        ->label('Antar ke Customer')
                        ->icon('heroicon-m-user')
                        ->color('info')
                        ->visible(
                            fn(Shipment $record) =>
                            $record->latest_track_status?->value === TrackStatus::Unloading->value
                                && ShipmentOwnership::canEdit(Filament::auth()->user(), $record)
                        )
                        ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Shipment $record, array $data) {
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $record), 403);
                            try {
                                $record->appendTrack(TrackStatus::DeliveryToCustomer, $data['note'] ?? null);
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
                            fn(Shipment $record) =>
                            $record->latest_track_status?->value === TrackStatus::DeliveryToCustomer->value
                                && ShipmentOwnership::canEdit(Filament::auth()->user(), $record)
                        )
                        ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                        ->action(function (Shipment $record, array $data) {
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $record), 403);
                            $record->appendTrack(TrackStatus::Delivered, $data['note'] ?? 'Terkirim');
                            Notification::make()->title('Shipment terkirim!')->success()->send();
                        }),

                    Action::make('hold')
                        ->label('Tahan')
                        ->icon('heroicon-m-pause-circle')
                        ->color('warning')
                        ->visible(
                            fn(Shipment $record) =>
                            ShipmentOwnership::canEdit(Filament::auth()->user(), $record)
                                && $record->latest_track_status !== TrackStatus::Hold
                                && ! in_array($record->latest_track_status, [TrackStatus::Delivered, TrackStatus::Cancelled], true)
                                && $record->latest_track_status !== null
                        )
                        ->form([\Filament\Forms\Components\Textarea::make('note')->label('Alasan')->rows(3)->required()])
                        ->action(function (Shipment $record, array $data) {
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $record), 403);
                            $record->appendTrack(TrackStatus::Hold, $data['note']);
                            Notification::make()->title('Shipment ditahan')->warning()->send();
                        }),

                    Action::make('cancel')
                        ->label('Batalkan')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->visible(
                            fn(Shipment $record) =>
                            $record->canCancel()
                                && ShipmentOwnership::canEdit(Filament::auth()->user(), $record)
                        )
                        ->form([\Filament\Forms\Components\Textarea::make('note')->label('Alasan')->rows(3)->required()])
                        ->requiresConfirmation()
                        ->action(function (Shipment $record, array $data) {
                            abort_unless(ShipmentOwnership::canEdit(Filament::auth()->user(), $record), 403);
                            $record->appendTrack(TrackStatus::Cancelled, $data['note']);
                            $record->forceFill([
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
                            fn(Shipment $record): string =>
                            OperationalShipmentPage::getUrl(['record' => $record->getKey()])
                        )
                        ->openUrlInNewTab(),

                    Action::make('printWaybill')
                        ->label('Cetak Waybill')
                        ->icon('heroicon-m-printer')
                        ->color('primary')
                        ->url(fn(Shipment $record): string => route('shipments.print.waybill', $record))
                        ->openUrlInNewTab()
                        ->visible(fn(Shipment $record) => auth()->user()?->can('print', $record)),

                    Action::make('printPackingList')
                        ->label('Cetak Packing List')
                        ->icon('heroicon-m-clipboard-document-list')
                        ->color('info')
                        ->url(fn(Shipment $record): string => route('shipments.print.packing', $record))
                        ->openUrlInNewTab()
                        ->visible(fn(Shipment $record) => auth()->user()?->can('print', $record)),

                    Action::make('printResi')
                        ->label('Cetak Resi')
                        ->icon('heroicon-m-document-text')
                        ->color('gray')
                        ->url(fn(Shipment $record): string => route('shipments.resi', $record))
                        ->openUrlInNewTab()
                        ->visible(fn(Shipment $record) => auth()->user()?->can('print', $record)),
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
