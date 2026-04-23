<?php

namespace Tests\Feature\FC;

use App\Enums\TrackStatus;
use App\Models\Branch;
use App\Models\City;
use App\Models\Customer;
use App\Models\Depot;
use App\Models\Shipment;
use App\Models\ShipmentTrack;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShipmentTrackingWorkflowTest extends TestCase
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

    private function createLandShipment(array $overrides = []): Shipment
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
            'mode' => 'land',
            'status' => 'pending',
            'service_type' => 'land_trucking',
            'request_type' => 'wa_telp',
            'priority' => 'normal',
            'assigned_depot_id' => null,
        ], $overrides));
    }

    private function trackShipment(Shipment $shipment, TrackStatus $status, array $extra = []): ShipmentTrack
    {
        return $shipment->appendTrack(
            $status,
            $extra['note'] ?? null,
            $extra['location'] ?? null,
            $extra['attachments'] ?? null,
            $extra['override'] ?? null,
            $extra['checkseet'] ?? null,
            $extra['plan_loading_time_at'] ?? null,
            $extra['plan_closing_time_at'] ?? null,
        );
    }

    /** @test */
    public function sea_shipment_allows_valid_sequential_transition(): void
    {
        $shipment = $this->createSeaShipment();
        $this->actingAs(User::factory()->create());

        // Pickup is auto-tracked at shipment creation
        $track = $this->trackShipment($shipment, TrackStatus::Handover);
        $this->assertEquals(TrackStatus::Handover, $track->status);
    }

    /** @test */
    public function sea_shipment_rejects_backward_transition(): void
    {
        $shipment = $this->createSeaShipment();
        $this->actingAs(User::factory()->create());

        $this->trackShipment($shipment, TrackStatus::Handover);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Tidak dapat mengubah status ke tahap sebelumnya');

        $this->trackShipment($shipment, TrackStatus::Pickup);
    }

    /** @test */
    public function sea_shipment_rejects_skipping_steps(): void
    {
        $shipment = $this->createSeaShipment();
        $this->actingAs(User::factory()->create());

        // Pickup is auto-tracked; next expected is Handover
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Status hanya dapat dilanjutkan ke tahap berikutnya secara berurutan');

        $this->trackShipment($shipment, TrackStatus::Stuffing);
    }

    /** @test */
    public function sea_shipment_rejects_re_tracking_same_status(): void
    {
        $shipment = $this->createSeaShipment();
        $this->actingAs(User::factory()->create());

        $this->trackShipment($shipment, TrackStatus::Handover);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('sudah pernah dicapai');

        $this->trackShipment($shipment, TrackStatus::Handover);
    }

    /** @test */
    public function sea_shipment_allows_hold_from_any_status(): void
    {
        $shipment = $this->createSeaShipment();
        $this->actingAs(User::factory()->create());

        $this->trackShipment($shipment, TrackStatus::Handover);
        $track = $this->trackShipment($shipment, TrackStatus::Hold, ['note' => 'Alasan penahanan yang cukup panjang']);

        $this->assertEquals(TrackStatus::Hold, $track->status);
    }

    /** @test */
    public function sea_shipment_allows_cancel_from_any_status(): void
    {
        $shipment = $this->createSeaShipment();
        $this->actingAs(User::factory()->create());

        $this->trackShipment($shipment, TrackStatus::Handover);
        $track = $this->trackShipment($shipment, TrackStatus::Cancelled, ['note' => 'Alasan pembatalan yang cukup panjang']);

        $this->assertEquals(TrackStatus::Cancelled, $track->status);
    }

    /** @test */
    public function sea_shipment_allows_resume_after_hold_to_next_step(): void
    {
        $shipment = $this->createSeaShipment();
        $this->actingAs(User::factory()->create());

        // Hold from Pickup (auto-tracked), then resume to Handover
        $this->trackShipment($shipment, TrackStatus::Hold, ['note' => 'Alasan penahanan yang cukup panjang']);
        $track = $this->trackShipment($shipment, TrackStatus::Handover);

        $this->assertEquals(TrackStatus::Handover, $track->status);
    }

    /** @test */
    public function sea_shipment_allows_rack_skip_handover_to_delivery_to_port(): void
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
            'vehicle_loading' => 'rack',
            'assigned_depot_id' => $depot->id,
        ]);
        $this->actingAs(User::factory()->create());

        $this->trackShipment($shipment, TrackStatus::Handover);
        $track = $this->trackShipment($shipment, TrackStatus::DeliveryToPort);

        $this->assertEquals(TrackStatus::DeliveryToPort, $track->status);
    }

    /** @test */
    public function sea_shipment_rejects_invalid_skip_even_for_rack(): void
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
            'vehicle_loading' => 'rack',
            'assigned_depot_id' => $depot->id,
        ]);
        $this->actingAs(User::factory()->create());

        $this->trackShipment($shipment, TrackStatus::Handover);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Status hanya dapat dilanjutkan ke tahap berikutnya secara berurutan');

        // Rack can skip Stuffing, but cannot skip DeliveryToPort from Handover
        $this->trackShipment($shipment, TrackStatus::Stacking);
    }

    /** @test */
    public function land_shipment_does_not_enforce_sea_transition_rules(): void
    {
        $shipment = $this->createLandShipment();
        $this->actingAs(User::factory()->create());

        // Pickup is auto-tracked at creation for land too
        // Land shipments don't have strict step enforcement in appendTrack
        $track = $this->trackShipment($shipment, TrackStatus::DeliveryToCustomer);
        $this->assertEquals(TrackStatus::DeliveryToCustomer, $track->status);
    }

    /** @test */
    public function sea_shipment_hold_requires_note_min_10_chars(): void
    {
        $shipment = $this->createSeaShipment();
        $this->actingAs(User::factory()->create());

        $this->trackShipment($shipment, TrackStatus::Handover);

        $this->expectException(ValidationException::class);

        $this->trackShipment($shipment, TrackStatus::Hold, ['note' => 'pendek']);
    }

    /** @test */
    public function sea_shipment_cancel_requires_note_min_10_chars(): void
    {
        $shipment = $this->createSeaShipment();
        $this->actingAs(User::factory()->create());

        $this->trackShipment($shipment, TrackStatus::Handover);

        $this->expectException(ValidationException::class);

        $this->trackShipment($shipment, TrackStatus::Cancelled, ['note' => 'pendek']);
    }

    /** @test */
    public function checksheet_ng_requires_note_min_10_chars_on_sea(): void
    {
        $shipment = $this->createSeaShipment();
        $this->actingAs(User::factory()->create());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Checksheet memiliki status NG');

        $this->trackShipment($shipment, TrackStatus::Handover, [
            'checkseet' => [
                [
                    'checkseet_status' => 'ng',
                    'model' => 'Toyota Avanza',
                    'no_rangka' => 'MHKM1BA2JBJ123456',
                    'no_mesin' => '1NR1234567',
                    'warna' => 'Silver',
                ],
            ],
            'note' => 'pendek',
        ]);
    }

    /** @test */
    public function checksheet_ok_does_not_require_note(): void
    {
        $shipment = $this->createSeaShipment();
        $this->actingAs(User::factory()->create());

        $track = $this->trackShipment($shipment, TrackStatus::Handover, [
            'checkseet' => [
                [
                    'checkseet_status' => 'ok',
                    'model' => 'Toyota Avanza',
                    'no_rangka' => 'MHKM1BA2JBJ123456',
                    'no_mesin' => '1NR1234567',
                    'warna' => 'Silver',
                ],
            ],
        ]);

        $this->assertEquals(TrackStatus::Handover, $track->status);
    }

    /** @test */
    public function append_track_saves_checkseet_and_plan_times(): void
    {
        $shipment = $this->createSeaShipment();
        $this->actingAs(User::factory()->create());

        $planLoading = now()->addDay()->format('Y-m-d H:i:s');
        $planClosing = now()->addDays(2)->format('Y-m-d H:i:s');

        $track = $this->trackShipment($shipment, TrackStatus::Handover, [
            'checkseet' => [
                [
                    'checkseet_status' => 'ok',
                    'model' => 'Toyota Avanza',
                    'no_rangka' => 'MHKM1BA2JBJ123456',
                    'no_mesin' => '1NR1234567',
                    'warna' => 'Silver',
                ],
            ],
            'plan_loading_time_at' => $planLoading,
            'plan_closing_time_at' => $planClosing,
        ]);

        $this->assertEquals(TrackStatus::Handover, $track->status);
        $this->assertNotNull($track->checkseet);
        $this->assertCount(1, $track->checkseet);
        $this->assertEquals('ok', $track->checkseet[0]['checkseet_status']);
        $this->assertEquals($planLoading, $track->plan_loading_time_at->format('Y-m-d H:i:s'));
        $this->assertEquals($planClosing, $track->plan_closing_time_at->format('Y-m-d H:i:s'));
    }

}
