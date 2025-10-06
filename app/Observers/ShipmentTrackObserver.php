<?php

namespace App\Observers;

use App\Enums\TrackStatus;
use App\Models\ShipmentTrack;
use App\Supports\ShipmentStatusSyncer;

class ShipmentTrackObserver
{
    private function label(null|string|TrackStatus $v): ?string
    {
        if ($v === null) return null;
        if ($v instanceof TrackStatus) return $v->label();
        return TrackStatus::tryFrom((string) $v)?->label() ?? strtoupper((string) $v);
    }

    public function created(ShipmentTrack $m): void
    {
        activity('tracking')
            ->performedOn($m)
            ->event('track_created')
            ->withProperties([
                'track_id'     => $m->getKey(),
                'shipment_id'  => $m->shipment_id,
                'code'         => $m->shipment?->code ?? '-',
                'status'       => $m->status instanceof TrackStatus ? $m->status->value : (string) $m->status,
                'status_label' => $this->label($m->status),
                'location'     => $m->location,
                'note'         => $m->note,
                'tracked_at'   => $m->tracked_at?->toIso8601String(),
            ])
            ->log('Tracking dibuat');

        app(ShipmentStatusSyncer::class)->syncFromTrack($m);
    }

    public function updated(ShipmentTrack $m): void
    {
        if ($m->wasChanged('status')) {
            $from = $m->getOriginal('status');
            $to   = $m->getAttribute('status');

            activity('tracking')
                ->performedOn($m)
                ->event('track_status_changed')
                ->withProperties([
                    'track_id'    => $m->getKey(),
                    'shipment_id' => $m->shipment_id,
                    'code'        => $m->shipment?->code ?? '-',
                    'from'        => $from instanceof TrackStatus ? $from->value : (string) $from,
                    'from_label'  => $this->label($from),
                    'to'          => $to instanceof TrackStatus ? $to->value : (string) $to,
                    'to_label'    => $this->label($to),
                    'tracked_at'  => $m->tracked_at?->toIso8601String(),
                ])
                ->log('Status tracking diubah');

            app(ShipmentStatusSyncer::class)->syncFromTrack($m);
        }

        if ($m->wasChanged('location')) {
            activity('tracking')
                ->performedOn($m)
                ->event('track_location_changed')
                ->withProperties([
                    'track_id'    => $m->getKey(),
                    'shipment_id' => $m->shipment_id,
                    'code'        => $m->shipment?->code ?? '-',
                    'from'        => $m->getOriginal('location'),
                    'to'          => $m->location,
                    'tracked_at'  => $m->tracked_at?->toIso8601String(),
                ])
                ->log('Lokasi tracking diubah');
        }

        if ($m->wasChanged('eta')) {
            $from = $m->getOriginal('eta');
            $to   = $m->eta;

            activity('tracking')
                ->performedOn($m)
                ->event('track_eta_changed')
                ->withProperties([
                    'track_id'    => $m->getKey(),
                    'shipment_id' => $m->shipment_id,
                    'code'        => $m->shipment?->code ?? '-',
                    'from'        => $from?->toIso8601String(),
                    'to'          => $to?->toIso8601String(),
                ])
                ->log('ETA tracking diubah');
        }

        $watched = ['route', 'lat', 'lng', 'proof_url', 'note'];
        $changed = array_values(array_filter($watched, fn($f) => $m->wasChanged($f)));

        if ($changed) {
            activity('tracking')
                ->performedOn($m)
                ->event('track_updated')
                ->withProperties([
                    'track_id'       => $m->getKey(),
                    'shipment_id'    => $m->shipment_id,
                    'code'           => $m->shipment?->code ?? '-',
                    'changed_fields' => $changed,
                ])
                ->log('Tracking diperbarui');
        }
    }

    public function deleted(ShipmentTrack $m): void
    {
        activity('tracking')
            ->performedOn($m)
            ->event('track_deleted')
            ->withProperties([
                'track_id'    => $m->getKey(),
                'shipment_id' => $m->shipment_id,
                'code'        => $m->shipment?->code ?? '-',
            ])
            ->log('Tracking dihapus');
    }

    public function restored(ShipmentTrack $m): void
    {
        activity('tracking')
            ->performedOn($m)
            ->event('track_restored')
            ->withProperties([
                'track_id'    => $m->getKey(),
                'shipment_id' => $m->shipment_id,
                'code'        => $m->shipment?->code ?? '-',
            ])
            ->log('Tracking dipulihkan');

        app(ShipmentStatusSyncer::class)->syncFromTrack($m);
    }
}
