<?php

namespace App\Observers;

use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Models\Depot;
use App\Models\Port;
use App\Models\Shipment;
use App\Models\Voyage;
use BackedEnum;
use Illuminate\Support\Facades\Schema;

class ShipmentObserver
{
    private function normalize(mixed $v): ?string
    {
        if ($v instanceof BackedEnum) return (string) $v->value;
        if (is_null($v)) return null;
        if (is_array($v)) return json_encode($v, JSON_UNESCAPED_UNICODE);
        if (is_object($v)) return method_exists($v, '__toString') ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE);
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

    private function depotQuery(int $branchId, array $portIds)
    {
        $q = Depot::query()
            ->where('branch_id', $branchId)
            ->whereIn('port_id', $portIds);

        if (Schema::hasColumn('depots', 'mode')) {
            $q->where('mode', 'sea');
        }

        return $q;
    }

    private function tryAssignDepot(Shipment $s): void
    {
        $mode = $s->mode?->value ?? (string) $s->mode;

        if ($mode !== ShipmentMode::Sea->value) {
            if ($s->assigned_depot_id) {
                $s->assigned_depot_id = null;
            }
            return;
        }

        if ($s->assigned_depot_id) return;

        if (empty($s->branch_id)) return;

        $portIds = [];

        if ($s->voyage_id) {
            $v = Voyage::select('pol_id', 'pod_id')->find($s->voyage_id);
            if ($v) $portIds = array_values(array_filter([$v->pol_id, $v->pod_id]));
        }

        if (empty($portIds)) {
            $codesOrNames = array_values(array_filter([
                trim((string) $s->pol),
                trim((string) $s->pod),
            ]));

            if ($codesOrNames) {
                $portIds = Port::where(function ($w) use ($codesOrNames) {
                    $w->whereIn('code', array_map('strtoupper', $codesOrNames))
                        ->orWhereIn('name', $codesOrNames);
                })
                    ->pluck('id')
                    ->all();
            }
        }

        if (!empty($portIds)) {
            $depotId = $this->depotQuery((int) $s->branch_id, $portIds)->value('id');

            if ($depotId) {
                $s->assigned_depot_id = $depotId;

                if (empty($s->coordinator_id)) {
                    $coord = Depot::whereKey($depotId)->value('coordinator_user_id');
                    if ($coord) $s->coordinator_id = $coord;
                }
            }
        }
    }

    public function saving(Shipment $s): void
    {
        if (($s->mode?->value ?? (string) $s->mode) !== 'sea') {
            $s->assigned_depot_id = null;
            return;
        }

        $this->tryAssignDepot($s);

        if ($s->assigned_depot_id && empty($s->coordinator_id)) {
            $coord = Depot::whereKey($s->assigned_depot_id)->value('coordinator_user_id');
            if ($coord) $s->coordinator_id = $coord;
        }
    }

    public function created(Shipment $s): void
    {
        $this->tryAssignDepot($s);
        if ($s->isDirty('assigned_depot_id') || $s->isDirty('coordinator_id')) {
            $s->saveQuietly();
        }

        $status = $this->normalize($s->status);
        $this->log('created', $s, [
            'status'       => $status,
            'status_label' => $this->statusLabel($status),
        ]);
    }

    public function updated(Shipment $s): void
    {
        $fromStatus = $this->normalize($s->getOriginal('status'));
        $toStatus   = $this->normalize($s->status);

        $changes = array_keys($s->getChanges());
        $changed = fn(string $k) => in_array($k, $changes, true);

        if (array_intersect($changes, ['voyage_id', 'pol', 'pod', 'branch_id', 'mode'])) {
            $this->tryAssignDepot($s);
            if ($s->isDirty('assigned_depot_id') || $s->isDirty('coordinator_id')) {
                $s->saveQuietly();
            }
        }

        if ($changed('cancelled_at') || ($changed('status') && $toStatus === ShipmentStatus::Cancelled->value)) {
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
        } elseif ($changed('status')) {
            $this->log('status_changed', $s, [
                'from'       => $fromStatus,
                'to'         => $toStatus,
                'from_label' => $this->statusLabel($fromStatus),
                'to_label'   => $this->statusLabel($toStatus),
            ]);
        }

        $other = array_values(array_diff($changes, [
            'updated_at',
            'created_at',
            'edited_fields',
            'last_edited_by',
            'status',
            'cancelled_at',
            'cancelled_by',
        ]));
        if (!empty($other)) {
            $diff = [];
            foreach ($other as $key) {
                $diff[$key] = [
                    'from' => $this->normalize($s->getOriginal($key)),
                    'to'   => $this->normalize($s->$key),
                ];
            }
            $this->log('updated', $s, ['changed_fields' => $other, 'diff' => $diff]);
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
}
