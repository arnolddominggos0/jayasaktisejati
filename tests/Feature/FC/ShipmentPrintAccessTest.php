<?php

namespace Tests\Feature\FC;

use App\Models\Branch;
use App\Models\City;
use App\Models\Customer;
use App\Models\Depot;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShipmentPrintAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'office_admin', 'field_coordinator'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }
    }

    private function createSeaShipment(Branch $branch, ?int $coordinatorId = null, ?int $depotId = null, array $overrides = []): Shipment
    {
        $origin = City::factory()->create();
        $dest = City::factory()->create();
        $customer = Customer::factory()->create();

        return Shipment::create(array_merge([
            'code' => null,
            'customer_id' => $customer->id,
            'receiver_id' => $customer->id,
            'origin_city_id' => $origin->id,
            'destination_city_id' => $dest->id,
            'branch_id' => $branch->id,
            'mode' => 'sea',
            'status' => 'pending',
            'service_type' => 'sea_freight',
            'request_type' => 'wa_telp',
            'priority' => 'normal',
            'assigned_depot_id' => $depotId,
            'coordinator_id' => $coordinatorId,
        ], $overrides));
    }

    private function createLandShipment(Branch $branch, ?int $coordinatorId = null, array $overrides = []): Shipment
    {
        $origin = City::factory()->create();
        $dest = City::factory()->create();
        $customer = Customer::factory()->create();

        return Shipment::create(array_merge([
            'code' => null,
            'customer_id' => $customer->id,
            'receiver_id' => $customer->id,
            'origin_city_id' => $origin->id,
            'destination_city_id' => $dest->id,
            'branch_id' => $branch->id,
            'mode' => 'land',
            'status' => 'pending',
            'service_type' => 'land_trucking',
            'request_type' => 'wa_telp',
            'priority' => 'normal',
            'assigned_depot_id' => null,
            'coordinator_id' => $coordinatorId,
        ], $overrides));
    }

    /** @test */
    public function fc_can_print_waybill_for_assigned_sea_shipment(): void
    {
        $branch = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);
        $fc = User::factory()->create(['branch_id' => $branch->id]);
        $fc->assignRole('field_coordinator');

        $depot = Depot::create([
            'code' => 'DP-JKT',
            'name' => 'Depo Jakarta',
            'mode' => 'sea',
            'branch_id' => $branch->id,
            'coordinator_user_id' => $fc->id,
        ]);

        $shipment = $this->createSeaShipment($branch, $fc->id, $depot->id);

        $this->actingAs($fc);

        $response = $this->get(route('shipments.print.waybill', $shipment));
        $response->assertOk();
    }

    /** @test */
    public function fc_can_print_packing_list_for_assigned_sea_shipment(): void
    {
        $branch = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);
        $fc = User::factory()->create(['branch_id' => $branch->id]);
        $fc->assignRole('field_coordinator');

        $depot = Depot::create([
            'code' => 'DP-JKT',
            'name' => 'Depo Jakarta',
            'mode' => 'sea',
            'branch_id' => $branch->id,
            'coordinator_user_id' => $fc->id,
        ]);

        $shipment = $this->createSeaShipment($branch, $fc->id, $depot->id);

        $this->actingAs($fc);

        $response = $this->get(route('shipments.print.packing', $shipment));
        $response->assertOk();
    }

    /** @test */
    public function fc_can_print_resi_for_assigned_sea_shipment(): void
    {
        $branch = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);
        $fc = User::factory()->create(['branch_id' => $branch->id]);
        $fc->assignRole('field_coordinator');

        $depot = Depot::create([
            'code' => 'DP-JKT',
            'name' => 'Depo Jakarta',
            'mode' => 'sea',
            'branch_id' => $branch->id,
            'coordinator_user_id' => $fc->id,
        ]);

        $shipment = $this->createSeaShipment($branch, $fc->id, $depot->id);

        $this->actingAs($fc);

        $response = $this->get(route('shipments.resi', $shipment));
        $response->assertOk();
    }

    /** @test */
    public function fc_denied_print_for_land_shipment(): void
    {
        $branch = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);
        $fc = User::factory()->create(['branch_id' => $branch->id]);
        $fc->assignRole('field_coordinator');

        $shipment = $this->createLandShipment($branch, $fc->id);

        $this->actingAs($fc);

        $response = $this->get(route('shipments.print.waybill', $shipment));
        $response->assertStatus(403);
    }

    /** @test */
    public function fc_denied_print_for_cross_branch_sea_shipment(): void
    {
        $jkt = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);
        $mdo = Branch::create(['code' => 'MDO', 'name' => 'Manado']);
        $fc = User::factory()->create(['branch_id' => $jkt->id]);
        $fc->assignRole('field_coordinator');

        $shipment = $this->createSeaShipment($mdo, $fc->id);

        $this->actingAs($fc);

        $response = $this->get(route('shipments.print.waybill', $shipment));
        $response->assertStatus(403);
    }

    /** @test */
    public function fc_denied_print_for_unassigned_sea_shipment(): void
    {
        $branch = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);
        $fc = User::factory()->create(['branch_id' => $branch->id]);
        $fc->assignRole('field_coordinator');

        $otherFc = User::factory()->create(['branch_id' => $branch->id]);
        $otherFc->assignRole('field_coordinator');

        $depot = Depot::create([
            'code' => 'DP-JKT',
            'name' => 'Depo Jakarta',
            'mode' => 'sea',
            'branch_id' => $branch->id,
            'coordinator_user_id' => $otherFc->id,
        ]);

        $shipment = $this->createSeaShipment($branch, $otherFc->id, $depot->id);

        $this->actingAs($fc);

        $response = $this->get(route('shipments.print.waybill', $shipment));
        $response->assertStatus(403);
    }

    /** @test */
    public function office_admin_can_print_sea_shipment_in_same_branch(): void
    {
        $branch = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);
        $admin = User::factory()->create(['branch_id' => $branch->id]);
        $admin->assignRole('office_admin');

        $shipment = $this->createSeaShipment($branch);

        $this->actingAs($admin);

        $response = $this->get(route('shipments.print.waybill', $shipment));
        $response->assertOk();
    }

    /** @test */
    public function super_admin_can_print_any_shipment(): void
    {
        $branch = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);
        $admin = User::factory()->create(['branch_id' => $branch->id]);
        $admin->assignRole('super_admin');

        $seaShipment = $this->createSeaShipment($branch);
        $landShipment = $this->createLandShipment($branch);

        $this->actingAs($admin);

        $this->get(route('shipments.print.waybill', $seaShipment))->assertOk();
        // Waybill is sea-only by design; resi works for any mode
        $this->get(route('shipments.resi', $landShipment))->assertOk();
    }

    /** @test */
    public function print_policy_blocks_draft_shipment(): void
    {
        $branch = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);
        $fc = User::factory()->create(['branch_id' => $branch->id]);
        $fc->assignRole('field_coordinator');

        $depot = Depot::create([
            'code' => 'DP-JKT',
            'name' => 'Depo Jakarta',
            'mode' => 'sea',
            'branch_id' => $branch->id,
            'coordinator_user_id' => $fc->id,
        ]);

        $shipment = $this->createSeaShipment($branch, $fc->id, $depot->id, ['status' => 'draft']);

        $this->actingAs($fc);

        $response = $this->get(route('shipments.print.waybill', $shipment));
        $response->assertStatus(403);
    }

    /** @test */
    public function print_policy_exists_and_delegates_correctly(): void
    {
        $branch = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);
        $fc = User::factory()->create(['branch_id' => $branch->id]);
        $fc->assignRole('field_coordinator');

        $depot = Depot::create([
            'code' => 'DP-JKT',
            'name' => 'Depo Jakarta',
            'mode' => 'sea',
            'branch_id' => $branch->id,
            'coordinator_user_id' => $fc->id,
        ]);

        $seaShipment = $this->createSeaShipment($branch, $fc->id, $depot->id);
        $landShipment = $this->createLandShipment($branch, $fc->id);

        $policy = new \App\Policies\ShipmentPolicy();

        $this->assertTrue($policy->print($fc, $seaShipment));
        $this->assertFalse($policy->print($fc, $landShipment));
    }
}
