<?php

namespace App\Observers;

use App\Enums\TrackStatus;
use App\Models\ShipmentTrack;
use Illuminate\Support\Arr;

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
            ->withProperties(array_merge($this->baseProps($m), [
                'status'       => $this->asValue($m->status),
                'status_label' => $this->label($m->status),
                'location'     => $m->location,
                'note'         => $m->note,
                'tracked_at'   => $m->tracked_at?->toIso8601String(),
            ]))
            ->log('Tracking dibuat');
    }

    public function updated(ShipmentTrack $m): void
    {
        // 1) Status berubah?
        if ($m->wasChanged('status')) {
            $from = $this->asValue($m->getOriginal('status'));
            $to   = $this->asValue($m->getAttribute('status'));

            activity('tracking')
                ->performedOn($m)
                ->event('track_status_changed')
                ->withProperties(array_merge($this->baseProps($m), [
                    'from'       => $from,
                    'from_label' => $this->label($from),
                    'to'         => $to,
                    'to_label'   => $this->label($to),
                    'tracked_at' => $m->tracked_at?->toIso8601String(),
                ]))
                ->log('Status tracking diubah');
        }

        // 2) Lokasi berubah?
        if ($m->wasChanged('location')) {
            activity('tracking')
                ->performedOn($m)
                ->event('track_location_changed')
                ->withProperties(array_merge($this->baseProps($m), [
                    'from'       => $m->getOriginal('location'),
                    'to'         => $m->location,
                    'tracked_at' => $m->tracked_at?->toIso8601String(),
                ]))
                ->log('Lokasi tracking diubah');
        }

        // 3) ETA berubah?
        if ($m->wasChanged('eta')) {
            $from = $m->getOriginal('eta');
            $to   = $m->eta;

            activity('tracking')
                ->performedOn($m)
                ->event('track_eta_changed')
                ->withProperties(array_merge($this->baseProps($m), [
                    'from' => $from?->toIso8601String(),
                    'to'   => $to?->toIso8601String(),
                ]))
                ->log('ETA tracking diubah');
        }

        // 4) Kolom lain
        $watched = ['route', 'lat', 'lng', 'proof_url', 'note'];
        $changed = array_values(array_filter($watched, fn ($f) => $m->wasChanged($f)));

        if ($changed) {
            activity('tracking')
                ->performedOn($m)
                ->event('track_updated')
                ->withProperties(array_merge($this->baseProps($m), [
                    'changed_fields' => $changed,
                ]))
                ->log('Tracking diperbarui');
        }
    }

    public function deleted(ShipmentTrack $m): void
    {
        activity('tracking')
            ->performedOn($m)
            ->event('track_deleted')
            ->withProperties($this->baseProps($m))
            ->log('Tracking dihapus');
    }

    public function restored(ShipmentTrack $m): void
    {
        activity('tracking')
            ->performedOn($m)
            ->event('track_restored')
            ->withProperties($this->baseProps($m))
            ->log('Tracking dipulihkan');
    }
}
