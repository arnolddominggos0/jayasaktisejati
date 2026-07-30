<?php

namespace App\Services\ContainerAllocation;

use App\Enums\UnitAllocationStatus;
use App\Models\Container;
use App\Models\Unit;
use App\Models\UnitInspection;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class ContainerAllocationService
{
    public function isUnitEligible(Unit $unit): bool
    {
        $rejected = UnitInspection::query()
            ->where('unit_id', $unit->id)
            ->where('stage', 'handover_depot')
            ->where('gate_decision', UnitInspection::GATE_RETURN_TO_PDC)
            ->exists();

        return ! $rejected;
    }

    public function assign(Unit $unit, Container $container): void
    {
        $this->guardEligible($unit);
        $this->guardContainerReady($container, 'diisi');
        $this->guardContainerConfigured($container);

        if ($unit->container_id !== null) {
            throw new DomainException(
                'Unit sudah berada di container lain. Gunakan aksi Pindahkan Container, bukan Masukkan ke Container.'
            );
        }

        $this->guardCapacity($container);

        DB::transaction(function () use ($unit, $container) {
            $unit->container_id = $container->id;
            $unit->allocation_status = UnitAllocationStatus::InContainer;
            $unit->save();
        });
    }

    public function move(Unit $unit, Container $newContainer): void
    {
        $this->guardEligible($unit);

        if ($unit->container_id === null) {
            throw new DomainException(
                'Unit belum berada di container mana pun. Gunakan aksi Masukkan ke Container.'
            );
        }

        $currentContainer = $unit->container;

        if ($currentContainer && $currentContainer->is_ready_for_stuffing) {
            throw new DomainException(
                'Container asal sudah ditandai Siap Stuffing. Batalkan tanda siap terlebih dahulu sebelum memindahkan isinya.'
            );
        }

        if ($currentContainer && $currentContainer->id === $newContainer->id) {
            throw new DomainException('Unit sudah berada di container tujuan.');
        }

        $this->guardContainerReady($newContainer, 'menerima pindahan');
        $this->guardContainerConfigured($newContainer);
        $this->guardCapacity($newContainer);

        DB::transaction(function () use ($unit, $newContainer) {
            $unit->container_id = $newContainer->id;
            $unit->allocation_status = UnitAllocationStatus::InContainer;
            $unit->save();
        });
    }

    public function remove(Unit $unit): void
    {
        if ($unit->container_id === null) {
            throw new DomainException('Unit memang belum berada di container mana pun.');
        }

        $currentContainer = $unit->container;

        if ($currentContainer && $currentContainer->is_ready_for_stuffing) {
            throw new DomainException(
                'Container ini sudah ditandai Siap Stuffing. Batalkan tanda siap terlebih dahulu sebelum mengeluarkan unit.'
            );
        }

        DB::transaction(function () use ($unit) {
            $unit->container_id = null;
            $unit->allocation_status = UnitAllocationStatus::NotInContainer;
            $unit->save();
        });
    }

    public function markContainerReady(Container $container, ?User $by = null): void
    {
        if ($container->filledCount() === 0) {
            throw new DomainException('Container ini belum berisi unit apa pun — tidak ada yang bisa ditandai siap.');
        }

        DB::transaction(function () use ($container, $by) {
            $container->is_ready_for_stuffing = true;
            $container->marked_ready_at = now();
            $container->marked_ready_by = $by?->id;
            $container->save();

            $container->units()->update(['allocation_status' => UnitAllocationStatus::ReadyForStuffing->value]);
        });
    }

    public function unmarkContainerReady(Container $container): void
    {
        DB::transaction(function () use ($container) {
            $container->is_ready_for_stuffing = false;
            $container->marked_ready_at = null;
            $container->marked_ready_by = null;
            $container->save();

            $container->units()->update(['allocation_status' => UnitAllocationStatus::InContainer->value]);
        });
    }

    // ── Guards ───────────────────────────────────────────────────────────────

    private function guardEligible(Unit $unit): void
    {
        if (! $this->isUnitEligible($unit)) {
            throw new DomainException(
                'Unit sudah ditandai Return to PDC — tidak bisa dimasukkan ke rencana alokasi.'
            );
        }
    }

    private function guardContainerConfigured(Container $container): void
    {
        if ($container->type === null || $container->capacity === null) {
            throw new DomainException(
                'Lengkapi tipe dan kapasitas container ini terlebih dahulu sebelum diisi unit.'
            );
        }
    }

    private function guardContainerReady(Container $container, string $action): void
    {
        if ($container->is_ready_for_stuffing) {
            throw new DomainException(
                "Container ini sudah ditandai Siap Stuffing — tidak bisa {$action} tanpa membatalkan tanda siap terlebih dahulu."
            );
        }
    }

    private function guardCapacity(Container $container): void
    {
        if ($container->isFull()) {
            throw new DomainException(
                "Container {$container->container_no} sudah penuh ({$container->capacity} unit). Pilih container lain."
            );
        }
    }
}
