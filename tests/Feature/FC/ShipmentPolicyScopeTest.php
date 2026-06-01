<?php

namespace Tests\Feature\FC;

use App\Models\Branch;
use App\Models\City;
use App\Models\Customer;
use App\Models\Depot;
use App\Models\Shipment;
use App\Models\User;
use App\Policies\ShipmentPolicy;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShipmentPolicyScopeTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'office_admin', 'field_coordinator'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }
    }

    private function createFcUser(Branch $branch): User
    {
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole('field_coordinator');

        return $user;
    }

    private function createCustomer(): Customer
    {
        return Customer::factory()->create();
    }

    private function createCities(): array
    {
        $origin = City::factory()->create();
        $dest = City::factory()->create();

        return [$origin->id, $dest->id];
    }

    private function createSeaShipment(Branch $branch, array $overrides = []): Shipment
    {
        [$originCityId, $destCityId] = $this->createCities();
        $customer = $this->createCustomer();

        $coordinatorId = $overrides['coordinator_id'] ?? null;
        unset($overrides['coordinator_id']);

        $shipment = Shipment::create(array_merge([
            'code' => null,
            'customer_id' => $customer->id,
            'receiver_id' => $customer->id,
            'origin_city_id' => $originCityId,
            'destination_city_id' => $destCityId,
            'branch_id' => $branch->id,
            'mode' => 'sea',
            'status' => 'draft',
            'service_type' => 'sea_freight',
            'request_type' => 'wa_telp',
            'priority' => 'normal',
            'assigned_depot_id' => null,
        ], $overrides));

        if ($coordinatorId !== null) {
            $shipment->forceFill(['coordinator_id' => $coordinatorId])->saveQuietly();
            $shipment->refresh();
        }

        return $shipment;
    }

    private function createLandShipment(Branch $branch, array $overrides = []): Shipment
    {
        [$originCityId, $destCityId] = $this->createCities();
        $customer = $this->createCustomer();

        $coordinatorId = $overrides['coordinator_id'] ?? null;
        unset($overrides['coordinator_id']);

        $shipment = Shipment::create(array_merge([
            'code' => null,
            'customer_id' => $customer->id,
            'receiver_id' => $customer->id,
            'origin_city_id' => $originCityId,
            'destination_city_id' => $destCityId,
            'branch_id' => $branch->id,
            'mode' => 'land',
            'status' => 'draft',
            'service_type' => 'land_trucking',
            'request_type' => 'wa_telp',
            'priority' => 'normal',
            'assigned_depot_id' => null,
        ], $overrides));

        if ($coordinatorId !== null) {
            $shipment->forceFill(['coordinator_id' => $coordinatorId])->saveQuietly();
            $shipment->refresh();
        }

        return $shipment;
    }

    /** @test */
    public function fc_can_access_sea_shipment_assigned_via_depot(): void
    {
        $branch = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);
        $fc = $this->createFcUser($branch);
        $depot = Depot::create([
            'code' => 'DP-JKT',
            'name' => 'Depo Jakarta',
            'mode' => 'sea',
            'branch_id' => $branch->id,
            'coordinator_user_id' => $fc->id,
        ]);

        $shipment = $this->createSeaShipment($branch, ['assigned_depot_id' => $depot->id]);

        $policy = new ShipmentPolicy();
        $this->assertTrue($policy->update($fc, $shipment));
    }

    /** @test */
    public function fc_can_access_sea_shipment_assigned_via_coordinator_id(): void
    {
        $branch = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);
        $fc = $this->createFcUser($branch);

        $shipment = $this->createSeaShipment($branch, ['coordinator_id' => $fc->id]);

        $policy = new ShipmentPolicy();
        $this->assertTrue($policy->update($fc, $shipment));
    }

    /** @test */
    public function fc_denied_cross_branch_sea_shipment(): void
    {
        $jkt = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);
        $mdo = Branch::create(['code' => 'MDO', 'name' => 'Manado']);
        $fc = $this->createFcUser($jkt);

        $shipment = $this->createSeaShipment($mdo, ['coordinator_id' => $fc->id]);

        $policy = new ShipmentPolicy();
        $this->assertFalse($policy->update($fc, $shipment));
    }

    /** @test */
    public function fc_denied_land_shipment(): void
    {
        $branch = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);
        $fc = $this->createFcUser($branch);

        $shipment = $this->createLandShipment($branch, ['coordinator_id' => $fc->id]);

        $policy = new ShipmentPolicy();
        $this->assertFalse($policy->update($fc, $shipment));
    }

    /** @test */
    public function fc_denied_unassigned_sea_shipment(): void
    {
        $branch = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);
        $fc = $this->createFcUser($branch);

        $shipment = $this->createSeaShipment($branch);

        $policy = new ShipmentPolicy();
        $this->assertFalse($policy->update($fc, $shipment));
    }

    /** @test */
    public function fc_query_returns_empty_when_scope_missing(): void
    {
        $branch = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);
        $fc = $this->createFcUser($branch);

        $this->actingAs($fc);

        $query = \App\Filament\FC\Resources\ShipmentResource::getEloquentQuery();
        $sql = $query->toSql();

        $this->assertStringContainsString('1=0', $sql);
    }

    /** @test */
    public function super_admin_behavior_unchanged(): void
    {
        $branch = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);
        $admin = User::factory()->create(['branch_id' => $branch->id]);
        $admin->assignRole('super_admin');

        $shipment = $this->createSeaShipment($branch);

        $this->assertTrue(Gate::forUser($admin)->check('update', $shipment));
    }

    /** @test */
    public function office_admin_behavior_unchanged(): void
    {
        $branch = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);
        $oa = User::factory()->create(['branch_id' => $branch->id]);
        $oa->assignRole('office_admin');

        $shipment = $this->createSeaShipment($branch);

        $this->assertTrue(Gate::forUser($oa)->check('update', $shipment));
    }
}
