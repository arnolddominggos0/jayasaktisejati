<?php

namespace App\Services\Stuffing;

use App\Enums\ContainerStuffingStatus;
use App\Enums\ShipmentStatus;
use App\Enums\UnitAllocationStatus;
use App\Models\Container;
use App\Models\ContainerReadinessSession;
use App\Models\Shipment;
use App\Models\Unit;
use App\Models\User;
use App\Services\DailyBriefingGate;
use DomainException;
use Illuminate\Support\Facades\DB;

class StuffingService
{
    public function checkPreconditions(Shipment $shipment): array
    {
        $checks = [];

        $status = $shipment->status instanceof ShipmentStatus
            ? $shipment->status
            : ShipmentStatus::tryFrom((string) $shipment->status);
        $checks['shipment_active'] = [
            'label' => 'Shipment aktif',
            'ok' => $status !== null && ! in_array($status, [
                ShipmentStatus::Draft, ShipmentStatus::Delivered, ShipmentStatus::Cancelled,
            ], true),
        ];
        $checks['briefing_ready'] = [
            'label' => 'Briefing Harian Ready',
            'ok' => $shipment->assigned_depot_id
                ? DailyBriefingGate::isReady($shipment->assigned_depot_id)
                : false,
        ];

        $readinessSession = ContainerReadinessSession::query()
            ->whereDate('session_date', today())
            ->first();
        $checks['container_readiness_done'] = [
            'label' => 'Container Readiness selesai',
            'ok' => (bool) ($readinessSession?->summary_sufficient),
        ];

        $checks['container_available'] = [
            'label' => 'Container sudah tersedia',
            'ok' => Unit::query()
                ->where('shipment_id', $shipment->id)
                ->whereHas('container', fn ($q) => $q->where('is_ready_for_stuffing', true))
                ->exists(),
        ];

        $ok = collect($checks)->every(fn (array $c) => $c['ok']);

        return ['ok' => $ok, 'checks' => $checks];
    }

    public function canOpenStuffing(Shipment $shipment): bool
    {
        return $this->checkPreconditions($shipment)['ok'];
    }

    public function openContainer(Container $container): void
    {
        $this->guardContainerReadyForStuffing($container);

        if ($container->stuffing_status === ContainerStuffingStatus::Ready) {
            $container->update([
                'stuffing_status' => ContainerStuffingStatus::Stuffing,
                'stuffing_started_at' => $container->stuffing_started_at ?? now(),
            ]);
        }
    }

    public function markUnitStuffed(Unit $unit, User $operator, ?string $remarks = null): void
    {
        $container = $this->guardUnitReadyToStuff($unit);

        DB::transaction(function () use ($unit, $container, $operator, $remarks) {
            $this->openContainer($container);

            $unit->update([
                'allocation_status' => UnitAllocationStatus::Stuffed,
                'stuffed_at' => now(),
                'stuffed_by' => $operator->id,
                'stuffing_remarks' => $remarks,
            ]);

            $this->refreshContainerCompletion($container->fresh());
        });
    }

    public function unmarkUnitStuffed(Unit $unit): void
    {
        if ($unit->allocation_status !== UnitAllocationStatus::Stuffed) {
            throw new DomainException('Unit ini belum ditandai selesai stuffing.');
        }

        DB::transaction(function () use ($unit) {
            $container = $unit->container;

            $unit->update([
                'allocation_status' => UnitAllocationStatus::ReadyForStuffing,
                'stuffed_at' => null,
                'stuffed_by' => null,
                'stuffing_remarks' => null,
            ]);

            if ($container && $container->stuffing_status === ContainerStuffingStatus::Full) {
                $container->update([
                    'stuffing_status' => ContainerStuffingStatus::Stuffing,
                    'stuffing_completed_at' => null,
                ]);
            }
        });
    }

    protected function refreshContainerCompletion(Container $container): void
    {
        if ($container->stuffing_status === ContainerStuffingStatus::Stuffing
            && $container->isStuffingComplete()
        ) {
            $container->update([
                'stuffing_status' => ContainerStuffingStatus::Full,
                'stuffing_completed_at' => now(),
            ]);
        }
    }
    public function shipmentStuffingSummary(Shipment $shipment): array
    {
        $containers = Container::query()
            ->whereHas('units', fn ($q) => $q->where('shipment_id', $shipment->id))
            ->get();

        $totalUnits = Unit::where('shipment_id', $shipment->id)
            ->whereIn('allocation_status', [
                UnitAllocationStatus::ReadyForStuffing->value,
                UnitAllocationStatus::Stuffed->value,
            ])
            ->count();

        $stuffedUnits = Unit::where('shipment_id', $shipment->id)
            ->where('allocation_status', UnitAllocationStatus::Stuffed->value)
            ->count();

        $state = match (true) {
            $containers->isEmpty() || $stuffedUnits === 0 => 'waiting_stuffing',
            $containers->every(fn (Container $c) => $c->stuffing_status === ContainerStuffingStatus::Full) => 'ready_loading',
            default => 'stuffing',
        };

        return [
            'state' => $state,
            'total_units' => $totalUnits,
            'stuffed_units' => $stuffedUnits,
            'containers' => $containers,
        ];
    }

    // ── Guards ──────────────────────────────────────────────────────────────

    private function guardContainerReadyForStuffing(Container $container): void
    {
        if (! $container->is_ready_for_stuffing) {
            throw new DomainException(
                "Container {$container->container_no} belum ditandai Siap Stuffing di Container Allocation."
            );
        }

        if ($container->stuffing_status === ContainerStuffingStatus::Full
            || $container->stuffing_status === ContainerStuffingStatus::ReadyLoading
        ) {
            throw new DomainException(
                "Container {$container->container_no} sudah selesai — tidak bisa dibuka ulang."
            );
        }
    }
    
    private function guardUnitReadyToStuff(Unit $unit): Container
    {
        if ($unit->container_id === null) {
            throw new DomainException(
                'Unit ini belum dialokasikan ke container mana pun — Stuffing tidak membuat rencana baru.'
            );
        }

        if ($unit->allocation_status === UnitAllocationStatus::Stuffed) {
            throw new DomainException('Unit ini sudah ditandai selesai stuffing sebelumnya.');
        }

        if ($unit->allocation_status !== UnitAllocationStatus::ReadyForStuffing) {
            throw new DomainException(
                'Unit ini belum dalam status Siap Stuffing (masih dalam proses alokasi).'
            );
        }

        $container = $unit->container;

        if (! $container) {
            throw new DomainException('Container untuk unit ini tidak ditemukan.');
        }

        $this->guardContainerReadyForStuffing($container);

        return $container;
    }
}
