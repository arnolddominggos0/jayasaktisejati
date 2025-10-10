<?php

namespace Database\Seeders;

use App\Enums\{ShipmentMode, ShipmentStatus, TrackStatus};
use App\Models\{Branch, City, Customer, Depot, Shipment};
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TestShipmentKpiWithTracksSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // --- Master data minimal untuk cakupan KPI Manado ---
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

            // Base waktu mundur 60 hari untuk seluruh sampel
            $base = now()->copy()->subDays(60)->startOfDay();

            // --- 1) NORMAL - ON TIME (≤19 hari) ---
            $s1 = $this->makeShipment(
                base: $base,
                sender: $sender,
                receiver: $receiver,
                origin: $jakarta,
                destination: $manado,
                branch: $manadoBranch,
                depotSea: $depotSea,
                priority: 'normal',
                containerNo: 'MSCU1234567',
                sealNo: 'SL001'
            );
            $this->makeTracksWithMilestones($s1, [
                [TrackStatus::Pickup,             $base->copy()->addDays(1)],
                [TrackStatus::Handover,           $base->copy()->addDays(2)],
                [TrackStatus::DeliveryToPort,     $base->copy()->addDays(3)],
                [TrackStatus::OnShip,             $base->copy()->addDays(5)],
                [TrackStatus::VesselDepart,       $base->copy()->addDays(5)->addHours(2)],
                [TrackStatus::VesselArrival,      $base->copy()->addDays(14)],
                [TrackStatus::Unloading,          $base->copy()->addDays(14)->addHours(6)],
                [TrackStatus::DeliveryToCustomer, $base->copy()->addDays(15)],
                [TrackStatus::Delivered,          $base->copy()->addDays(16)], // 16 hari -> On Time normal
            ]);

            // --- 2) NORMAL - LATE (>19 hari) ---
            $s2 = $this->makeShipment(
                base: $base,
                sender: $sender,
                receiver: $receiver,
                origin: $jakarta,
                destination: $manado,
                branch: $manadoBranch,
                depotSea: $depotSea,
                priority: 'normal',
                containerNo: 'MSCU7654321',
                sealNo: 'SL999'
            );
            $this->makeTracksWithMilestones($s2, [
                [TrackStatus::Pickup,             $base->copy()->addDays(1)],
                [TrackStatus::Handover,           $base->copy()->addDays(3)],
                [TrackStatus::DeliveryToPort,     $base->copy()->addDays(5)],
                [TrackStatus::OnShip,             $base->copy()->addDays(7)],
                [TrackStatus::VesselDepart,       $base->copy()->addDays(7)->addHours(4)],
                [TrackStatus::VesselArrival,      $base->copy()->addDays(18)],
                [TrackStatus::Unloading,          $base->copy()->addDays(18)->addHours(8)],
                [TrackStatus::DeliveryToCustomer, $base->copy()->addDays(20)],
                [TrackStatus::Delivered,          $base->copy()->addDays(21)], // 21 hari -> Late normal
            ]);

            // --- 3) URGENT - ON TIME (≤17 hari) ---
            $s3 = $this->makeShipment(
                base: $base,
                sender: $sender,
                receiver: $receiver,
                origin: $jakarta,
                destination: $manado,
                branch: $manadoBranch,
                depotSea: $depotSea,
                priority: 'urgent',
                containerNo: 'MSCU8888888',
                sealNo: 'SLU01'
            );
            $this->makeTracksWithMilestones($s3, [
                [TrackStatus::Pickup,             $base->copy()->addDays(1)],
                [TrackStatus::Handover,           $base->copy()->addDays(1)->addHours(6)],
                [TrackStatus::DeliveryToPort,     $base->copy()->addDays(2)],
                [TrackStatus::OnShip,             $base->copy()->addDays(4)],
                [TrackStatus::VesselDepart,       $base->copy()->addDays(4)->addHours(2)],
                [TrackStatus::VesselArrival,      $base->copy()->addDays(12)],
                [TrackStatus::Unloading,          $base->copy()->addDays(12)->addHours(4)],
                [TrackStatus::DeliveryToCustomer, $base->copy()->addDays(13)],
                [TrackStatus::Delivered,          $base->copy()->addDays(15)], // 15 hari -> On Time urgent
            ]);

            // --- 4) URGENT - LATE (>17 hari) ---
            $s4 = $this->makeShipment(
                base: $base,
                sender: $sender,
                receiver: $receiver,
                origin: $jakarta,
                destination: $manado,
                branch: $manadoBranch,
                depotSea: $depotSea,
                priority: 'urgent',
                containerNo: 'MSCU9999999',
                sealNo: 'SLU99'
            );
            $this->makeTracksWithMilestones($s4, [
                [TrackStatus::Pickup,             $base->copy()->addDays(1)],
                [TrackStatus::Handover,           $base->copy()->addDays(3)],
                [TrackStatus::DeliveryToPort,     $base->copy()->addDays(4)],
                [TrackStatus::OnShip,             $base->copy()->addDays(6)],
                [TrackStatus::VesselDepart,       $base->copy()->addDays(6)->addHours(4)],
                [TrackStatus::VesselArrival,      $base->copy()->addDays(16)],
                [TrackStatus::Unloading,          $base->copy()->addDays(16)->addHours(10)],
                [TrackStatus::DeliveryToCustomer, $base->copy()->addDays(18)],
                [TrackStatus::Delivered,          $base->copy()->addDays(19)], // 19 hari -> Late urgent (threshold 17)
            ]);

            $this->command?->info('Seeded KPI samples: Normal On-Time/Late & Urgent On-Time/Late, lengkap dengan timeline & timestamp konsisten.');
        });
    }

    /**
     * Buat Shipment SEA FCL Port->Door dengan timestamp konsisten
     */
    protected function makeShipment(
        Carbon $base,
        Customer $sender,
        Customer $receiver,
        City $origin,
        City $destination,
        Branch $branch,
        Depot $depotSea,
        string $priority,
        string $containerNo,
        string $sealNo
    ): Shipment {
        return Shipment::withoutEvents(function () use (
            $base,
            $sender,
            $receiver,
            $origin,
            $destination,
            $branch,
            $depotSea,
            $priority,
            $containerNo,
            $sealNo
        ) {
            $s = new Shipment();
            $s->branch_id            = $branch->id;
            $s->customer_id          = $sender->id;
            $s->receiver_id          = $receiver->id;
            $s->origin_city_id       = $origin->id;
            $s->destination_city_id  = $destination->id;
            $s->mode                 = ShipmentMode::Sea->value;
            $s->service_type         = \App\Enums\ServiceType::SeaFreight->value;
            $s->service_option       = 'fcl';
            $s->delivery_scope       = \App\Enums\DeliveryScope::PortToDoor->value;
            $s->cargo_type           = \App\Enums\CargoType::General->value;
            $s->priority             = $priority; // 'normal' | 'urgent'
            $s->request_type         = \App\Enums\RequestType::WA_TELP->value;
            $s->assigned_depot_id    = $depotSea->id;
            $s->container_no         = $containerNo;
            $s->seal_no              = $sealNo;

            // tanggal acuan permintaan & ETA kasar
            $s->requested_at         = $base->copy();
            $s->eta                  = $base->copy()->addDays($priority === 'urgent' ? 16 : 18); // hanya untuk contoh

            $s->status               = ShipmentStatus::Pending->value;
            $s->code = method_exists(Shipment::class, 'generateCode')
                ? Shipment::generateCode(ShipmentMode::Sea->value)
                : ('JSS' . now()->format('ym') . 'SH' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT));

            // pastikan created_at/updated_at konsisten dengan base
            $s->created_at = $base->copy();
            $s->updated_at = $base->copy();

            $s->saveQuietly();

            return $s;
        });
    }

    /**
     * Buat tracks, rebuild milestones, dan set status akhir.
     * Penting: created_at/updated_at track disamakan dengan tracked_at
     */
    protected function makeTracksWithMilestones(Shipment $shipment, array $timeline): void
    {
        foreach ($timeline as [$status, $at]) {
            $tracked = Carbon::parse($at)->copy();
            $shipment->tracks()->create([
                'status'     => $status->value,
                'note'       => null,
                'location'   => null,
                'tracked_at' => $tracked,
                'created_at' => $tracked,
                'updated_at' => $tracked,
            ]);
        }

        // Re-hitung milestone di Shipment dari tracks
        $shipment->rebuildMilestonesFromTracks();

        // Set status & updated_at mengikuti track terakhir
        $last = end($timeline);
        if ($last && ($last[0] === TrackStatus::Delivered)) {
            $shipment->status     = ShipmentStatus::Delivered->value;
        } else {
            $shipment->status     = ShipmentStatus::Transit->value;
        }
        $lastAt = $last ? Carbon::parse($last[1]) : $shipment->created_at;
        $shipment->updated_at = $lastAt;

        $shipment->saveQuietly();
    }
}
