<?php

namespace App\Observers;

use App\Enums\TrackStatus;
use App\Models\ShipmentTrack;
use App\Services\InspectionDraftAutoCreate;
use App\Services\LoadingSessionAutoCreate;
use App\Supports\ShipmentStatusSyncer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ShipmentTrackObserver
{
    private function label(null|string|TrackStatus $v): ?string
    {
        if ($v === null) {
            return null;
        }
        if ($v instanceof TrackStatus) {
            return $v->label();
        }

        return TrackStatus::tryFrom((string) $v)?->label() ?? strtoupper((string) $v);
    }

    private array $milestoneTouchFields = [
        'status',
        'tracked_at',
        'location',
        'proof_url',
    ];

    private function clearKpiCache(?int $shipmentId): void
    {
        if (! $shipmentId) {
            return;
        }

        Cache::forget("kpi:badge:{$shipmentId}");
        Cache::forget("kpi:summary:{$shipmentId}");
    }

    private function rebuildMilestones(ShipmentTrack $m): void
    {
        $shipment = $m->shipment;
        if (! $shipment) {
            return;
        }

        $shipment->rebuildMilestonesFromTracks();

        $shipment->touch();

        $this->clearKpiCache($shipment->getKey());
    }

    public function creating(ShipmentTrack $m): void
    {
        LoadingSessionAutoCreate::ensureForTrack($m);
        InspectionDraftAutoCreate::ensureForTrack($m);
    }

    public function created(ShipmentTrack $m): void
    {
        activity('tracking')
            ->performedOn($m)
            ->event('track_created')
            ->withProperties([
                'track_id' => $m->getKey(),
                'shipment_id' => $m->shipment_id,
                'code' => $m->shipment?->code ?? '-',
                'status' => $m->status instanceof TrackStatus ? $m->status->value : (string) $m->status,
                'status_label' => $this->label($m->status),
                'location' => $m->location,
                'note' => $m->note,
                'tracked_at' => $m->tracked_at?->toIso8601String(),
            ])
            ->log('Tracking dibuat');

        app(ShipmentStatusSyncer::class)->syncFromTrack($m);

        $this->rebuildMilestones($m);
    }

    public function updating(ShipmentTrack $m): void
    {
        if ($m->isDirty('status')) {
            $newStatus = $m->status instanceof TrackStatus
                ? $m->status
                : TrackStatus::tryFrom((string) $m->status);

            if ($newStatus && $newStatus === TrackStatus::UnitLoading) {
                $shipment = $m->shipment;
                if ($shipment && ! LoadingSessionAutoCreate::canTransitionTo($shipment, $newStatus)) {
                    throw new ValidationException(
                        Validator::make([], []),
                        trans('Loading checkpoint belum selesai. Selesaikan semua pemeriksaan loading sebelum mengubah status ke "Dimuat di Kapal".')
                    );
                }
            }
        }
    }

    public function updated(ShipmentTrack $m): void
    {
        if ($m->wasChanged('status')) {
            $from = $m->getOriginal('status');
            $to = $m->getAttribute('status');

            activity('tracking')
                ->performedOn($m)
                ->event('track_status_changed')
                ->withProperties([
                    'track_id' => $m->getKey(),
                    'shipment_id' => $m->shipment_id,
                    'code' => $m->shipment?->code ?? '-',
                    'from' => $from instanceof TrackStatus ? $from->value : (string) $from,
                    'from_label' => $this->label($from),
                    'to' => $to instanceof TrackStatus ? $to->value : (string) $to,
                    'to_label' => $this->label($to),
                    'tracked_at' => $m->tracked_at?->toIso8601String(),
                ])
                ->log('Status tracking diubah');

            app(ShipmentStatusSyncer::class)->syncFromTrack($m);
        }

        if ($m->wasChanged('location')) {
            activity('tracking')
                ->performedOn($m)
                ->event('track_location_changed')
                ->withProperties([
                    'track_id' => $m->getKey(),
                    'shipment_id' => $m->shipment_id,
                    'code' => $m->shipment?->code ?? '-',
                    'from' => $m->getOriginal('location'),
                    'to' => $m->location,
                    'tracked_at' => $m->tracked_at?->toIso8601String(),
                ])
                ->log('Lokasi tracking diubah');
        }

        if ($m->wasChanged('eta')) {
            $from = $m->getOriginal('eta');
            $to = $m->eta;

            activity('tracking')
                ->performedOn($m)
                ->event('track_eta_changed')
                ->withProperties([
                    'track_id' => $m->getKey(),
                    'shipment_id' => $m->shipment_id,
                    'code' => $m->shipment?->code ?? '-',
                    'from' => $from?->toIso8601String(),
                    'to' => $to?->toIso8601String(),
                ])
                ->log('ETA tracking diubah');
        }

        $watched = ['route', 'lat', 'lng', 'proof_url', 'note'];
        $changed = array_values(array_filter($watched, fn ($f) => $m->wasChanged($f)));
        if ($changed) {
            activity('tracking')
                ->performedOn($m)
                ->event('track_updated')
                ->withProperties([
                    'track_id' => $m->getKey(),
                    'shipment_id' => $m->shipment_id,
                    'code' => $m->shipment?->code ?? '-',
                    'changed_fields' => $changed,
                ])
                ->log('Tracking diperbarui');
        }

        if (Arr::first($this->milestoneTouchFields, fn ($f) => $m->wasChanged($f))) {
            $this->rebuildMilestones($m);
        }
    }

    public function deleted(ShipmentTrack $m): void
    {
        activity('tracking')
            ->performedOn($m)
            ->event('track_deleted')
            ->withProperties([
                'track_id' => $m->getKey(),
                'shipment_id' => $m->shipment_id,
                'code' => $m->shipment?->code ?? '-',
            ])
            ->log('Tracking dihapus');

        $this->rebuildMilestones($m);
    }

    public function restored(ShipmentTrack $m): void
    {
        activity('tracking')
            ->performedOn($m)
            ->event('track_restored')
            ->withProperties([
                'track_id' => $m->getKey(),
                'shipment_id' => $m->shipment_id,
                'code' => $m->shipment?->code ?? '-',
            ])
            ->log('Tracking dipulihkan');

        app(ShipmentStatusSyncer::class)->syncFromTrack($m);

        $this->rebuildMilestones($m);
    }
}
