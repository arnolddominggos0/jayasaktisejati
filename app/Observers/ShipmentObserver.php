<?php

namespace App\Observers;

use App\Models\Shipment;
use BackedEnum;

class ShipmentObserver
{
    private function normalize(mixed $v): string
    {
        if ($v instanceof BackedEnum) return (string) $v->value;
        return (string) $v;
    }

    private function log(string $event, Shipment $s, array $props = []): void
    {
        activity('permintaan_pengiriman')
            ->performedOn($s)
            ->causedBy(auth()->user())
            ->event($event)
            ->withProperties(array_merge(['code' => $s->code], $props))
            ->log($event);
    }

    public function created(Shipment $s): void
    {
        $this->log('created', $s, ['status' => $this->normalize($s->status)]);
    }

    public function updated(Shipment $s): void
    {
        $changes  = $s->getChanges();
        $original = $s->getOriginal();

        $changedKeys = array_diff(
            array_keys($changes),
            ['updated_at', 'created_at', 'edited_fields', 'last_edited_by']
        );

        if (array_key_exists('cancelled_at', $changes)) {
            $this->log($s->cancelled_at ? 'cancelled' : 'uncancelled', $s);
            $changedKeys = array_diff($changedKeys, ['cancelled_at', 'cancelled_by']);
        }

        if (array_key_exists('status', $changes)) {
            $this->log('status_changed', $s, [
                'from' => $this->normalize($original['status'] ?? null),
                'to'   => $this->normalize($changes['status']),
            ]);
            $changedKeys = array_diff($changedKeys, ['status']);
        }

        if (array_key_exists('route_summary', $changes)) {
            $this->log('route_updated', $s);
            $changedKeys = array_diff($changedKeys, ['route_summary']);
        }

        if (!empty($changedKeys)) {
            $this->log('updated', $s);
        }
    }

    public function deleted(Shipment $s): void { $this->log('deleted', $s); }
    public function restored(Shipment $s): void { $this->log('restored', $s); }
}
