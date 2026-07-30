<?php

use App\Enums\TrackStatus;
use App\Models\Shipment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Shipment::query()
            ->where('status', '!=', 'draft')
            ->with([
                'units',
                'tracks' => fn ($q) => $q->whereNotNull('tracked_at'),
            ])
            ->chunkById(100, function ($shipments) {
                foreach ($shipments as $shipment) {
                    DB::transaction(function () use ($shipment) {
                        $trackedStatuses = $shipment->tracks;

                        foreach ($shipment->units as $unit) {
                            $unit->setRelation('shipment', $shipment);
                            $unit->ensureTrackSkeleton();

                            $existing = $unit->tracks()->get()->keyBy(
                                fn ($t) => $t->status instanceof TrackStatus ? $t->status->value : (string) $t->status
                            );

                            foreach ($trackedStatuses as $st) {
                                $value = $st->status instanceof TrackStatus ? $st->status->value : (string) $st->status;
                                $row = $existing->get($value);

                                if ($row && empty($row->tracked_at)) {
                                    $row->updateQuietly([
                                        'tracked_at' => $st->tracked_at,
                                        'note' => $st->note,
                                        'location' => $st->location,
                                        'checkseet' => $st->checkseet,
                                        'check_result' => $st->check_result,
                                        'plan_loading_time_at' => $st->plan_loading_time_at,
                                        'plan_closing_time_at' => $st->plan_closing_time_at,
                                    ]);
                                }
                            }
                        }
                    });
                }
            });
    }

    public function down(): void
    {
        DB::table('unit_tracks')->truncate();
    }
};
