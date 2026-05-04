<?php

namespace Tests\Feature\FC;

use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Enums\TrackStatus;
use App\Models\Shipment;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ShipmentTrackingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_sea_shipment_rejects_skipping_status_transition(): void
    {
        $shipment = Shipment::factory()->create([
            'mode' => ShipmentMode::Sea->value,
            'status' => ShipmentStatus::Pending->value, 
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Status hanya dapat dilanjutkan ke tahap berikutnya');

        $shipment->appendTrack(TrackStatus::Stuffing, 'skip directly');
    }

    public function test_sea_shipment_accepts_immediate_next_transition(): void
    {
        $shipment = Shipment::factory()->create([
            'mode' => ShipmentMode::Sea->value,
            'status' => ShipmentStatus::Pending->value, 
        ]);

        $track = $shipment->appendTrack(TrackStatus::Pickup, 'pickup done');

        $this->assertNotNull($track->tracked_at);
        $this->assertSame(TrackStatus::Pickup->value, $track->status->value);
    }

    public function test_hold_requires_note_minimum_length_for_sea(): void
    {
        $shipment = Shipment::factory()->create([
            'mode' => ShipmentMode::Sea->value,
            'status' => ShipmentStatus::Pending->value, 
        ]);

        $this->expectException(ValidationException::class);
        $shipment->appendTrack(TrackStatus::Hold, 'short');
    }

    public function test_ng_checksheet_requires_note_minimum_length_for_sea(): void
    {
        $shipment = Shipment::factory()->create([
            'mode' => ShipmentMode::Sea->value,
            'status' => ShipmentStatus::Pending->value, 
        ]);

        $shipment->appendTrack(TrackStatus::Pickup, 'pickup done');

        $this->expectException(ValidationException::class);

        $shipment->appendTrack(
            TrackStatus::Handover,
            'short',
            null,
            null,
            null,
            [
                [
                    'checkseet_status' => 'ng',
                    'model' => 'X',
                    'no_rangka' => 'RNG',
                    'no_mesin' => 'ENG',
                    'warna' => 'Hitam',
                ],
            ],
            now()->toDateTimeString(),
            now()->addHour()->toDateTimeString(),
        );
    }

    public function test_land_shipment_transition_behavior_is_untouched(): void
    {
        $shipment = Shipment::factory()->create([
            'mode' => ShipmentMode::Land->value,
            'status' => ShipmentStatus::Transit->value,
        ]);

        $track = $shipment->appendTrack(TrackStatus::DeliveryToCustomer, 'langsung antar');

        $this->assertNotNull($track->tracked_at);
        $this->assertSame(TrackStatus::DeliveryToCustomer->value, $track->status->value);
    }

    public function test_sea_order_includes_handover_trucking_before_delivery_to_customer(): void
    {
        $orderValues = array_map(fn(TrackStatus $status) => $status->value, TrackStatus::orderSea());

        $handoverTruckingIndex = array_search(TrackStatus::HandoverTrucking->value, $orderValues, true);
        $deliveryIndex = array_search(TrackStatus::DeliveryToCustomer->value, $orderValues, true);

        $this->assertNotFalse($handoverTruckingIndex);
        $this->assertNotFalse($deliveryIndex);
        $this->assertLessThan($deliveryIndex, $handoverTruckingIndex);
    }
}
