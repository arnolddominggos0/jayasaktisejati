<?php

namespace Tests\Feature\FC;

use App\Enums\ShipmentStatus;
use App\Enums\UnitAllocationStatus;
use App\Models\Container;
use App\Models\ContainerReadinessSession;
use App\Models\Shipment;
use App\Models\Unit;
use App\Models\UnitInspection;
use App\Services\ContainerAllocation\ContainerAllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Sprint UNIT-05 — regression tests codifying the Unit-centric acceptance
 * criteria: updating one Unit must never change a sibling Unit, and yard
 * readiness must be computed from Unit (+ UnitInspection), not Shipment.
 *
 * "Ready Loading" / "Waiting Inspection" queries mirror the join pattern used
 * by App\Filament\FC\Pages\YardDashboard::buildReadyLoadingTable() /
 * buildWaitingInspectionTable() (units + shipment_tracks[handover] +
 * unit_inspections[handover_depot]), minus the Filament panel depot-scoping
 * (which requires a mounted Livewire/panel context not otherwise used in this
 * test suite).
 */
class UnitStateIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUnits(Shipment $shipment, int $count = 2): array
    {
        return collect(range(1, $count))
            ->map(fn (int $i) => $shipment->units()->create([
                'model_no' => "MODEL-{$i}",
                'reg_no' => "REG-{$i}",
                'chassis_no' => "CHASSIS-{$i}",
                'qty' => 1,
            ]))
            ->all();
    }

    public function test_submitting_one_unit_inspection_does_not_affect_sibling_unit(): void
    {
        $shipment = Shipment::factory()->create();
        [$unitA, $unitB] = $this->makeUnits($shipment);

        $unitA->inspections()->create([
            'stage' => 'handover_depot',
            'status' => UnitInspection::STATUS_PASSED,
            'gate_decision' => UnitInspection::GATE_ACCEPT,
            'submitted_at' => now(),
        ]);

        $this->assertSame(1, $unitA->fresh()->inspections()->count());
        $this->assertSame(0, $unitB->fresh()->inspections()->count());
        $this->assertNull($unitB->fresh()->allocation_status);
    }

    public function test_assigning_container_to_one_unit_does_not_affect_sibling_unit(): void
    {
        $shipment = Shipment::factory()->create();
        [$unitA, $unitB] = $this->makeUnits($shipment);

        $session = ContainerReadinessSession::create([
            'session_date' => today(),
            'unit_count' => 2,
            'container_need' => 1,
            'container_available' => 1,
            'gap' => 0,
            'summary_sufficient' => true,
        ]);

        $container = Container::create([
            'container_no' => 'TGHU1234567',
            'type' => '20',
            'capacity' => 5,
            'container_readiness_session_id' => $session->id,
        ]);

        app(ContainerAllocationService::class)->assign($unitA, $container);

        $unitA->refresh();
        $unitB->refresh();

        $this->assertSame($container->id, $unitA->container_id);
        $this->assertSame(UnitAllocationStatus::InContainer, $unitA->allocation_status);

        $this->assertNull($unitB->container_id);
        $this->assertNull($unitB->allocation_status);
    }

    public function test_shipment_status_change_does_not_cascade_to_unit_fields(): void
    {
        $shipment = Shipment::factory()->create(['status' => ShipmentStatus::Pending->value]);
        [$unitA, $unitB] = $this->makeUnits($shipment);

        $unitA->update(['allocation_status' => UnitAllocationStatus::ReadyForStuffing->value, 'container_display' => 'MSKU7654321']);

        $shipment->status = ShipmentStatus::Hold->value;
        $shipment->save();

        $this->assertSame(UnitAllocationStatus::ReadyForStuffing, $unitA->fresh()->allocation_status);
        $this->assertSame('MSKU7654321', $unitA->fresh()->container_display);
        $this->assertNull($unitB->fresh()->allocation_status);
        $this->assertNull($unitB->fresh()->container_display);
    }

    public function test_ready_loading_and_waiting_inspection_are_computed_per_unit(): void
    {
        $shipment = Shipment::factory()->create();
        [$unitA, $unitB] = $this->makeUnits($shipment);

        // Shared physical handover event (shipment-level, by design).
        $shipment->tracks()->create([
            'status' => 'handover',
            'tracked_at' => now()->subDay(),
        ]);

        // Only Unit A has passed handover inspection.
        $unitA->inspections()->create([
            'stage' => 'handover_depot',
            'status' => UnitInspection::STATUS_PASSED,
            'gate_decision' => UnitInspection::GATE_ACCEPT,
            'submitted_at' => now(),
        ]);

        $exitExists = fn ($q) => $q
            ->from('shipment_tracks as st_exit')
            ->whereColumn('st_exit.shipment_id', 's.id')
            ->whereNotNull('st_exit.tracked_at')
            ->where('st_exit.status', 'stuffing');

        $readyLoadingIds = Unit::query()
            ->select('units.id')
            ->join('shipments as s', 's.id', '=', 'units.shipment_id')
            ->join('shipment_tracks as st_h', fn ($j) => $j
                ->on('st_h.shipment_id', '=', 's.id')
                ->where('st_h.status', 'handover')
                ->whereNotNull('st_h.tracked_at'))
            ->join('unit_inspections as ui', fn ($j) => $j
                ->on('ui.unit_id', '=', 'units.id')
                ->where('ui.stage', 'handover_depot')
                ->whereNotNull('ui.submitted_at')
                ->whereIn('ui.gate_decision', ['accept', 'allow_with_remark']))
            ->whereNotExists($exitExists)
            ->pluck('units.id')
            ->all();

        $waitingInspectionIds = Unit::query()
            ->select('units.id')
            ->join('shipments as s', 's.id', '=', 'units.shipment_id')
            ->join('shipment_tracks as st_h', fn ($j) => $j
                ->on('st_h.shipment_id', '=', 's.id')
                ->where('st_h.status', 'handover')
                ->whereNotNull('st_h.tracked_at'))
            ->whereNotExists($exitExists)
            ->whereNotExists(fn ($q) => $q
                ->from('unit_inspections as ui')
                ->whereColumn('ui.unit_id', 'units.id')
                ->where('ui.stage', 'handover_depot')
                ->whereNotNull('ui.submitted_at'))
            ->pluck('units.id')
            ->all();

        $this->assertSame([$unitA->id], $readyLoadingIds);
        $this->assertSame([$unitB->id], $waitingInspectionIds);
    }
}
