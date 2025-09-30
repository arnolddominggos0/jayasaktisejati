<?php

namespace App\Observers;

use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Models\Depot;
use App\Models\Shipment;
use App\Models\Voyage;
use BackedEnum;

class ShipmentObserver
{
    private function normalize(mixed $v): ?string
    {
        if ($v instanceof BackedEnum) {
            return (string) $v->value;
        }

        if (is_null($v)) {
            return null;
        }

        if (is_array($v)) {
            return json_encode($v, JSON_UNESCAPED_UNICODE);
        }

        if (is_object($v)) {
            return method_exists($v, '__toString')
                ? (string) $v
                : json_encode($v, JSON_UNESCAPED_UNICODE);
        }

        return (string) $v;
    }


    private function statusLabel(?string $code): ?string
    {
        return $code ? (ShipmentStatus::tryFrom($code)?->label() ?? $code) : null;
    }

    private function log(string $event, Shipment $s, array $props = []): void
    {
        activity('permintaan_pengiriman')
            ->performedOn($s)
            ->causedBy(auth_user())
            ->event($event)
            ->withProperties(array_merge(['code' => $s->code], $props))
            ->log($event);
    }

    private function tryAssignDepot(Shipment $s): void
    {
        if (($s->mode?->value ?? (string)$s->mode) !== 'sea') {
            $s->assigned_depot_id = null;
            return;
        }

        $portIds = [];
        if ($s->voyage_id) {
            $v = \App\Models\Voyage::select('port_from_id', 'port_to_id')->find($s->voyage_id);
            if ($v) $portIds = array_filter([$v->port_from_id, $v->port_to_id]);
        }

        if (empty($portIds)) {
            $codes = array_filter([strtoupper((string)$s->pol), strtoupper((string)$s->pod)]);
            if ($codes) {
                $portIds = \App\Models\Port::whereIn('code', $codes)->pluck('id')->all();
            }
        }

        if ($portIds) {
            $depotId = \App\Models\Depot::where('mode', 'sea')
                ->where('branch_id', $s->branch_id)
                ->whereIn('port_id', $portIds)
                ->value('id');
            if ($depotId) {
                $s->assigned_depot_id = $depotId;
            }
        }
    }


    public function created(Shipment $s): void
    {
        $this->tryAssignDepot($s);
        if ($s->isDirty('assigned_depot_id')) {
            $s->saveQuietly();
        }

        $status = $this->normalize($s->status);
        $this->log('created', $s, [
            'status'      => $status,
            'status_label' => $this->statusLabel($status),
        ]);

        $this->ensureAssignedDepot($s);
    }


    public function updated(Shipment $s): void
    {
        $fromStatus = $this->normalize($s->getOriginal('status'));
        $toStatus   = $this->normalize($s->status);

        $changes    = array_keys($s->getChanges());
        $changed    = fn(string $k) => in_array($k, $changes, true);

        $cancelChanged  = $changed('cancelled_at');
        $statusChanged  = $changed('status');

        $keys = array_keys($s->getChanges());
        if (array_intersect($keys, ['voyage_id', 'pol', 'pod', 'branch_id', 'mode'])) {
            $this->tryAssignDepot($s);
            if ($s->isDirty('assigned_depot_id')) {
                $s->saveQuietly();
            }
        }

        if (
            $cancelChanged ||
            ($statusChanged && $toStatus === ShipmentStatus::Cancelled->value)
        ) {
            if ($s->cancelled_at) {
                $this->log('cancelled', $s, [
                    'from'       => $fromStatus,
                    'to'         => $toStatus,
                    'from_label' => $this->statusLabel($fromStatus),
                    'to_label'   => $this->statusLabel($toStatus),
                    'at'         => $s->cancelled_at?->toDateTimeString(),
                ]);
            } else {
                $this->log('uncancelled', $s, [
                    'from'       => $fromStatus ?: 'cancelled',
                    'to'         => $toStatus,
                    'from_label' => $this->statusLabel($fromStatus ?: 'cancelled'),
                    'to_label'   => $this->statusLabel($toStatus),
                ]);
            }

            $changes = array_values(array_diff($changes, ['status', 'cancelled_at', 'cancelled_by']));
        } elseif ($statusChanged) {
            $this->log('status_changed', $s, [
                'from'       => $fromStatus,
                'to'         => $toStatus,
                'from_label' => $this->statusLabel($fromStatus),
                'to_label'   => $this->statusLabel($toStatus),
            ]);
            $changes = array_values(array_diff($changes, ['status']));
        }

        if (in_array('route_summary', $changes, true)) {
            $this->log('route_updated', $s, [
                'from' => (string) ($s->getOriginal('route_summary') ?? ''),
                'to'   => (string) $s->route_summary,
            ]);
            $changes = array_values(array_diff($changes, ['route_summary']));
        }

        $other = array_values(array_diff($changes, [
            'updated_at',
            'created_at',
            'edited_fields',
            'last_edited_by',
        ]));

        if (!empty($other)) {
            $diff = [];
            foreach ($other as $key) {
                $diff[$key] = [
                    'from' => $this->normalize($s->getOriginal($key)),
                    'to'   => $this->normalize($s->$key),
                ];
            }
            $this->log('updated', $s, [
                'changed_fields' => $other,
                'diff'           => $diff,
            ]);
        }

        if (
            in_array('voyage_id', $changes, true) ||
            in_array('branch_id', $changes, true) ||
            in_array('mode', $changes, true)      ||
            ($s->mode === ShipmentMode::Sea && in_array('assigned_depot_id', $changes, true) && $s->assigned_depot_id === null)
        ) {
            $this->ensureAssignedDepot($s);
        }
    }

    public function deleted(Shipment $s): void
    {
        $this->log('deleted', $s);
    }
    public function restored(Shipment $s): void
    {
        $this->log('restored', $s);
    }

    private function ensureAssignedDepot(Shipment $s): void
    {
        if (($s->mode?->value ?? (string)$s->mode) !== 'sea' || empty($s->branch_id)) {
            if ($s->assigned_depot_id) {
                $s->forceFill(['assigned_depot_id' => null])->saveQuietly();
            }
            return;
        }

        $voyId = $s->voyage_id;
        if (! $voyId) return;

        $voy = Voyage::with(['portFrom', 'portTo'])->find($voyId);
        if (! $voy) return;

        $candidateId = Depot::query()
            ->where('branch_id', $s->branch_id)
            ->where('mode', 'sea')
            ->where('port_id', $voy->port_from_id)
            ->value('id');

        if (! $candidateId) {
            $candidateId = Depot::query()
                ->where('branch_id', $s->branch_id)
                ->where('mode', 'sea')
                ->where('port_id', $voy->port_to_id)
                ->value('id');
        }

        if ($candidateId && $candidateId !== $s->assigned_depot_id) {
            $payload = ['assigned_depot_id' => $candidateId];

            if (empty($s->coordinator_id)) {
                $coord = Depot::whereKey($candidateId)->value('coordinator_user_id');
                if ($coord) $payload['coordinator_id'] = $coord;
            }

            $s->forceFill($payload)->saveQuietly();
        }
    }
}
