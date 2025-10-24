<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShipmentTrack;
use App\Enums\TrackStatus;

class BackfillShipmentTracksForKpiSeeder extends Seeder
{
    public function run(): void
    {
        ShipmentTrack::query()
            ->whereNull('actual_at')
            ->orWhereNull('status')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $t) {
                    $t->actual_at = $t->actual_at ?? $t->tracked_at ?? $t->created_at ?? now();

                    if (empty($t->remarks) && !empty($t->note)) $t->remarks = $t->note;
                    if (empty($t->note) && !empty($t->remarks)) $t->note = $t->remarks;

                    if ($t->status === 'stuffing')       $t->status = TrackStatus::Stuffing->value;
                    if ($t->status === 'unloading')      $t->status = TrackStatus::Unloading->value;
                    if ($t->status === 'vessel_depart')  $t->status = TrackStatus::VesselDepart->value;
                    if ($t->status === 'vessel_arrival') $t->status = TrackStatus::VesselArrival->value;

                    $t->saveQuietly();
                }
            });
    }
}
