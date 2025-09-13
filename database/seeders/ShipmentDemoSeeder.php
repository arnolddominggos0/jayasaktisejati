<?php

namespace Database\Seeders;

use App\Enums\ShipmentStatus;
use App\Models\Customer;
use App\Models\Shipment;
use Illuminate\Database\Seeder;

class ShipmentDemoSeeder extends Seeder
{
    public function run(): void
    {
        $shipments = Shipment::factory()->count(30)->create([
            'status' => ShipmentStatus::Draft->value,
        ]);

        foreach ($shipments as $s) {
            $receiverId = Customer::query()
                ->when($s->customer_id, fn($q) => $q->where('id', '!=', $s->customer_id))
                ->inRandomOrder()
                ->value('id');

            $receiverName = $receiverId ? Customer::find($receiverId)?->name : 'PT Penerima Nusantara';

            $from = $s->route_from ?: ($s->originCity->name ?? 'Jakarta');
            $to   = $s->route_to   ?: ($s->destinationCity->name ?? 'Manado');

            $s->update([
                'receiver_id'   => $receiverId,
                'receiver_name' => $receiverName,
                'route_summary' => "{$from} → Hub A → {$to}",
                'status'        => ShipmentStatus::Pickup->value,
            ]);

            if (random_int(0, 1) === 1) {
                $s->update(['status' => ShipmentStatus::Delivered->value]);
            }
        }
    }
}
