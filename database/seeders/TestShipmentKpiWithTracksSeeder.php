<?php

namespace Database\Seeders;

use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Enums\TrackStatus;
use App\Models\Branch;
use App\Models\City;
use App\Models\Customer;
use App\Models\Depot;
use App\Models\Shipment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TestShipmentKpiWithTracksSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $manadoBranch = Branch::withoutEvents(function () {
                $b = Branch::query()->find(2);
                if (! $b) {
                    $b = new Branch();
                    $b->id   = 2;
                    $b->code = 'MND';
                    $b->name = 'Manado';
                    $b->saveQuietly();
                } else {
                    $b->fill([
                        'code' => $b->code ?: 'MND',
                        'name' => $b->name ?: 'Manado',
                    ])->saveQuietly();
                }
                return $b;
            });

            $jakarta = City::query()->firstOrCreate(['id' => 1], ['name' => 'Jakarta']);
            $manado  = City::query()->firstOrCreate(['id' => 2], ['name' => 'Manado']);

            $depotSea = Depot::query()->firstOrCreate(
                [
                    'branch_id' => $manadoBranch->id,
                    'mode'      => ShipmentMode::Sea->value,
                ],
                [
                    'name' => 'Depo Laut Manado',
                ]
            );

            [$sender, $receiver] = Customer::withoutEvents(function () {
                $sender = Customer::query()->firstOrCreate(
                    ['email' => 'sender@test.local'],
                    [
                        'code'    => 'CTM-SEED-001',
                        'name'    => 'Sender Test',
                        'phone'   => '08110000001',
                        'address' => 'Jakarta',
                    ]
                );

                $receiver = Customer::query()->firstOrCreate(
                    ['email' => 'receiver@test.local'],
                    [
                        'code'    => 'CTM-SEED-002',
                        'name'    => 'Receiver Test',
                        'phone'   => '08120000002',
                        'address' => 'Manado',
                    ]
                );

                return [$sender, $receiver];
            });

            $base = now()->copy()->subDays(60)->startOfDay();

            $sOnTime = Shipment::withoutEvents(function () use (
                $sender,
                $receiver,
                $jakarta,
                $manado,
                $manadoBranch,
                $depotSea,
                $base
            ) {
                $s = new Shipment();
                $s->branch_id            = $manadoBranch->id;
                $s->customer_id          = $sender->id;
                $s->receiver_id          = $receiver->id;
                $s->origin_city_id       = $jakarta->id;
                $s->destination_city_id  = $manado->id;
                $s->mode                 = ShipmentMode::Sea->value;
                $s->service_type         = \App\Enums\ServiceType::SeaFreight->value;
                $s->service_option       = 'fcl';
                $s->delivery_scope       = \App\Enums\DeliveryScope::PortToDoor->value;
                $s->cargo_type           = \App\Enums\CargoType::General->value;
                $s->priority             = 'normal';
                $s->request_type         = \App\Enums\RequestType::WA_TELP->value;
                $s->assigned_depot_id    = $depotSea->id;
                $s->container_no         = 'MSCU1234567';
                $s->seal_no              = 'SL001';
                $s->requested_at         = $base->copy();
                $s->status               = ShipmentStatus::Pending->value;
                $s->code                 = method_exists(Shipment::class, 'generateCode')
                    ? Shipment::generateCode(ShipmentMode::Sea->value)
                    : ('JSS' . now()->format('ym') . 'SH' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT));
                $s->save();

                return $s;
            });

            $this->makeTracksWithMilestones(
                $sOnTime,
                [
                    [TrackStatus::Pickup,             $base->copy()->addDays(1)],
                    [TrackStatus::Handover,           $base->copy()->addDays(2)],
                    [TrackStatus::DeliveryToPort,     $base->copy()->addDays(3)],
                    [TrackStatus::OnShip,             $base->copy()->addDays(5)],
                    [TrackStatus::VesselDepart,       $base->copy()->addDays(5)->addHours(2)],
                    [TrackStatus::VesselArrival,      $base->copy()->addDays(14)],
                    [TrackStatus::Unloading,          $base->copy()->addDays(14)->addHours(6)],
                    [TrackStatus::DeliveryToCustomer, $base->copy()->addDays(15)],
                    [TrackStatus::Delivered,          $base->copy()->addDays(16)],
                ]
            );

            $sLate = Shipment::withoutEvents(function () use (
                $sender,
                $receiver,
                $jakarta,
                $manado,
                $manadoBranch,
                $depotSea,
                $base
            ) {
                $s = new Shipment();
                $s->branch_id            = $manadoBranch->id;
                $s->customer_id          = $sender->id;
                $s->receiver_id          = $receiver->id;
                $s->origin_city_id       = $jakarta->id;
                $s->destination_city_id  = $manado->id;
                $s->mode                 = ShipmentMode::Sea->value;
                $s->service_type         = \App\Enums\ServiceType::SeaFreight->value;
                $s->service_option       = 'fcl';
                $s->delivery_scope       = \App\Enums\DeliveryScope::PortToDoor->value;
                $s->cargo_type           = \App\Enums\CargoType::General->value;
                $s->priority             = 'normal';
                $s->request_type         = \App\Enums\RequestType::WA_TELP->value;
                $s->assigned_depot_id    = $depotSea->id;
                $s->container_no         = 'MSCU7654321';
                $s->seal_no              = 'SL999';
                $s->requested_at         = $base->copy();
                $s->status               = ShipmentStatus::Pending->value;
                $s->code                 = method_exists(Shipment::class, 'generateCode')
                    ? Shipment::generateCode(ShipmentMode::Sea->value)
                    : ('JSS' . now()->format('ym') . 'SH' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT));
                $s->save();

                return $s;
            });

            $this->makeTracksWithMilestones(
                $sLate,
                [
                    [TrackStatus::Pickup,             $base->copy()->addDays(1)],
                    [TrackStatus::Handover,           $base->copy()->addDays(3)],
                    [TrackStatus::DeliveryToPort,     $base->copy()->addDays(5)],
                    [TrackStatus::OnShip,             $base->copy()->addDays(7)],
                    [TrackStatus::VesselDepart,       $base->copy()->addDays(7)->addHours(4)],
                    [TrackStatus::VesselArrival,      $base->copy()->addDays(18)],
                    [TrackStatus::Unloading,          $base->copy()->addDays(18)->addHours(8)],
                    [TrackStatus::DeliveryToCustomer, $base->copy()->addDays(20)],
                    [TrackStatus::Delivered,          $base->copy()->addDays(21)],
                ]
            );

            $this->command?->info('Seeded 2 shipments: 1 On-Time, 1 Late untuk KPI Manado.');
        });
    }

    protected function makeTracksWithMilestones(Shipment $shipment, array $timeline): void
    {
        foreach ($timeline as [$status, $at]) {
            $shipment->tracks()->create([
                'status'     => $status->value,
                'note'       => null,
                'location'   => null,
                'tracked_at' => Carbon::parse($at),
            ]);
        }

        $shipment->rebuildMilestonesFromTracks();

        $last = end($timeline);
        if ($last && ($last[0] === TrackStatus::Delivered)) {
            $shipment->status = ShipmentStatus::Delivered->value;
        } else {
            $shipment->status = ShipmentStatus::Transit->value;
        }

        $shipment->saveQuietly();
    }
}
