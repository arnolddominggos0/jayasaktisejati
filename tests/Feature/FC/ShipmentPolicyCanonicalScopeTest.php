<?php

namespace Tests\Feature\FC;

use App\Models\Branch;
use App\Models\City;
use App\Models\Customer;
use App\Models\Depot;
use App\Models\Shipment;
use App\Models\User;
use App\Policies\ShipmentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShipmentPolicyCanonicalScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'office_admin', 'field_coordinator'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }
    }

    private function createFcUser(Branch $branch, array $scope = []): User
    {
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole('field_coordinator');

        if (! empty($scope)) {
            $user->forceFill($scope)->saveQuietly();
        }

        return $user;
    }

    private function createSeaShipment(Branch $branch, array $overrides = []): Shipment
    {
        $origin = City::factory()->create();
        $dest = City::factory()->create();
        $customer = Customer::factory()->create();

        $coordinatorId = $overrides['coordinator_id'] ?? null;
        unset($overrides['coordinator_id']);

        $shipment = Shipment::create(array_merge([
            'code' => null,
            'customer_id' => $customer->id,
            'receiver_id' => $customer->id,
            'origin_city_id' => $origin->id,
            'destination_city_id' => $dest->id,
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

    /** @test */
    public function fc_can_access_via_canonical_scope_unit_id(): void
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

        // Populate canonical scope
        $fc->forceFill([
            'scope_branch_id' => $branch->id,
            'scope_unit_id' => $depot->id,
            'scope_unit_type' => 'depot',
        ])->saveQuietly();

        $shipment = $this->createSeaShipment($branch, ['assigned_depot_id' => $depot->id]);

        $policy = new ShipmentPolicy();
        $this->assertTrue($policy->update($fc, $shipment));
    }

    /** @test */
    public function fc_denied_when_canonical_scope_unit_mismatches(): void
    {
        $branch = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);
        $fc = $this->createFcUser($branch);

        $depotA = Depot::create([
            'code' => 'DP-A',
            'name' => 'Depo A',
            'mode' => 'sea',
            'branch_id' => $branch->id,
            'coordinator_user_id' => $fc->id,
        ]);

        // Populate canonical scope to depotA
        $fc->forceFill([
            'scope_branch_id' => $branch->id,
            'scope_unit_id' => $depotA->id,
            'scope_unit_type' => 'depot',
        ])->saveQuietly();

        // Shipment assigned to a different depot (not owned by this FC)
        $depotB = Depot::create([
            'code' => 'DP-B',
            'name' => 'Depo B',
            'mode' => 'sea',
            'branch_id' => $branch->id,
            'coordinator_user_id' => null,
        ]);

        $shipment = $this->createSeaShipment($branch, ['assigned_depot_id' => $depotB->id]);

        $policy = new ShipmentPolicy();
        $this->assertFalse($policy->update($fc, $shipment));
    }

    /** @test */
    public function fc_fallback_to_branch_id_when_scope_branch_id_null(): void
    {
        $branch = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);
        $fc = $this->createFcUser($branch); // no scope fields

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
}
