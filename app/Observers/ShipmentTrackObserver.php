<?php

namespace App\Observers;

use App\Enums\TrackStatus;
use App\Models\ShipmentTrack;
use App\Support\ShipmentStatusSyncer as SupportShipmentStatusSyncer;
use App\Supports\ShipmentStatusSyncer;

class ShipmentTrackObserver
{
    private function asValue(null|string|TrackStatus $v): ?string
    {
        if ($v === null) return null;
        return $v instanceof TrackStatus ? $v->value : (string) $v;
    }

    private function label(null|string|TrackStatus $v): ?string
    {
        if ($v === null) return null;
        if ($v instanceof TrackStatus) return $v->label();
        return TrackStatus::tryFrom((string) $v)?->label() ?? strtoupper((string) $v);
    }

    private function baseProps(ShipmentTrack $m): array
    {
        return [
            'track_id'    => $m->getKey(),
            'shipment_id' => $m->shipment_id,
            'code'        => $m->shipment?->code ?? '-',
        ];
    }

    public function created(ShipmentTrack $m): void
    {
        activity('tracking')
            ->performedOn($m)
            ->event('track_created')
            ->withProperties([
                'track_id'    => $m->getKey(),
                'shipment_id' => $m->shipment_id,
                'code'        => $m->shipment?->code ?? '-',
                'status'       => $m->status instanceof TrackStatus ? $m->status->value : (string) $m->status,
                'status_label' => $m->status instanceof TrackStatus ? $m->status->label() : (TrackStatus::tryFrom((string) $m->status)?->label() ?? strtoupper((string) $m->status)),
                'location'     => $m->location,
                'note'         => $m->note,
                'tracked_at'   => $m->tracked_at?->toIso8601String(),
            ])
            ->log('Tracking dibuat');

        app(ShipmentStatusSyncer::class)->syncFromTrack($m);
    }


    public function updated(ShipmentTrack $m): void
    {
        // 1) Status berubah?
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
                    'from'       => $from instanceof TrackStatus ? $from->value : (string) $from,
                    'from_label' => $from instanceof TrackStatus ? $from->label() : (TrackStatus::tryFrom((string) $from)?->label() ?? strtoupper((string) $from)),
                    'to'         => $to instanceof TrackStatus ? $to->value : (string) $to,
                    'to_label'   => $to instanceof TrackStatus ? $to->label() : (TrackStatus::tryFrom((string) $to)?->label() ?? strtoupper((string) $to)),
                    'tracked_at' => $m->tracked_at?->toIso8601String(),
                ])
                ->log('Status tracking diubah');

            app(ShipmentStatusSyncer::class)->syncFromTrack($m);
        }

        // 2) Lokasi berubah?
        if ($m->wasChanged('location')) {
            activity('tracking')
                ->performedOn($m)
                ->event('track_location_changed')
                ->withProperties([
                    'track_id'    => $m->getKey(),
                    'shipment_id' => $m->shipment_id,
                    'code'        => $m->shipment?->code ?? '-',
                    'from'       => $m->getOriginal('location'),
                    'to'         => $m->location,
                    'tracked_at' => $m->tracked_at?->toIso8601String(),
                ])
                ->log('Lokasi tracking diubah');
        }


        // 3) ETA berubah?
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
                    'from' => $from?->toIso8601String(),
                    'to'   => $to?->toIso8601String(),
                ])
                ->log('ETA tracking diubah');
        }

        // 4) Kolom lain
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
