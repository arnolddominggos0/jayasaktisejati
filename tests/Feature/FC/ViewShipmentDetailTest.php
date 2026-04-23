<?php

namespace Tests\Feature\FC;

use App\Models\Branch;
use App\Models\City;
use App\Models\Customer;
use App\Models\Depot;
use App\Models\Port;
use App\Models\Shipment;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ViewShipmentDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'office_admin', 'field_coordinator'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }
    }

    private function createSeaShipment(array $overrides = []): Shipment
    {
        $branch = Branch::firstOrCreate(
            ['code' => 'JKT'],
            ['name' => 'Jakarta']
        );
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
            'assigned_depot_id' => null,
        ], $overrides));
    }

    /** @test */
    public function fc_view_shipment_page_loads_with_null_fields(): void
    {
        $branch = Branch::firstOrCreate(['code' => 'JKT'], ['name' => 'Jakarta']);
        $fc = User::factory()->create(['branch_id' => $branch->id]);
        $fc->assignRole('field_coordinator');

        $depot = Depot::create([
            'code' => 'DP-JKT',
            'name' => 'Depo Jakarta',
            'mode' => 'sea',
            'branch_id' => $branch->id,
            'coordinator_user_id' => $fc->id,
        ]);

        $shipment = $this->createSeaShipment([
            'pic_name' => null,
            'pic_phone' => null,
            'pickup_contact_name' => null,
            'pickup_contact_phone' => null,
            'delivery_contact_name' => null,
            'delivery_contact_phone' => null,
            'vessel_name' => null,
            'voyage' => null,
            'pol' => null,
            'pod' => null,
            'container_no' => null,
            'seal_no' => null,
            'vehicle_loading' => null,
            'assigned_depot_id' => $depot->id,
        ]);

        $this->actingAs($fc);

        $response = $this->get("/fc/shipments/{$shipment->id}");
        $response->assertOk();
    }

    /** @test */
    public function fc_view_shipment_page_loads_with_complete_sea_data(): void
    {
        $branch = Branch::firstOrCreate(['code' => 'JKT'], ['name' => 'Jakarta']);
        $fc = User::factory()->create(['branch_id' => $branch->id]);
        $fc->assignRole('field_coordinator');

        $depot = Depot::create([
            'code' => 'DP-JKT',
            'name' => 'Depo Jakarta',
            'mode' => 'sea',
            'branch_id' => $branch->id,
            'coordinator_user_id' => $fc->id,
        ]);

        Port::create(['code' => 'TPR', 'name' => 'Tanjung Priok', 'city' => 'Jakarta']);

        $shipment = $this->createSeaShipment([
            'pic_name' => 'Budi Santoso',
            'pic_phone' => '081234567890',
            'pickup_contact_name' => 'Andi Wijaya',
            'pickup_contact_phone' => '081298765432',
            'delivery_contact_name' => 'Citra Lestari',
            'delivery_contact_phone' => '081376543210',
            'vessel_name' => 'KM Maju Jaya',
            'voyage' => 'VY123',
            'pol' => 'Tanjung Priok',
            'pod' => 'Bitung',
            'container_no' => 'CONT-001',
            'seal_no' => 'SEAL-001',
            'container_qty' => 2,
            'vehicle_loading' => 'rack',
            'assigned_depot_id' => $depot->id,
            'priority' => 'urgent',
        ]);

        Unit::create([
            'shipment_id' => $shipment->id,
            'model_no' => 'Toyota Avanza',
            'reg_no' => 'B 1234 ABC',
            'chassis_no' => 'MHKM123456',
            'engine_no' => '1NR12345',
            'color' => 'Silver',
        ]);

        $this->actingAs($fc);

        $response = $this->get("/fc/shipments/{$shipment->id}");
        $response->assertOk();
        $response->assertSee('Budi Santoso');
        $response->assertSee('KM Maju Jaya');
        $response->assertSee('Tanjung Priok');
        $response->assertSee('Depo Jakarta');
        $response->assertSee('Rack');
        $response->assertSee('Urgent');
    }
}
