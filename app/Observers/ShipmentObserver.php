<?php

namespace App\Observers;

use App\Models\Shipment;
use BackedEnum;

class ShipmentObserver
{
    /**
     * Normalisasi nilai enum / string ke string murni.
     */
    private function normalize(mixed $v): string
    {
        if ($v instanceof BackedEnum) {
            return (string) $v->value;
        }
        return (string) $v;
    }

    private function log(string $event, Shipment $s, array $props = []): void
    {
        activity('permintaan_pengiriman')
            ->performedOn($s)
            ->causedBy(auth()->user())   // boleh null saat seeding; dianggap "Sistem"
            ->event($event)
            ->withProperties(array_merge([
                'code' => $s->code,
            ], $props))
            ->log($event);
    }

    public function created(Shipment $s): void
    {
        $this->log('created', $s, [
            'status' => $this->normalize($s->status),
        ]);
    }

    public function updated(Shipment $s): void
    {
        // Hati-hati: getOriginal()/getChanges() bisa kembalikan enum atau string
        $changes  = $s->getChanges();
        $original = $s->getOriginal();

        // Status berubah?
        if (array_key_exists('status', $changes)) {
            $this->log('status_changed', $s, [
                'from' => $this->normalize($original['status'] ?? null),
                'to'   => $this->normalize($changes['status']),
            ]);
        }

        // Route summary berubah?
        if (array_key_exists('route_summary', $changes)) {
            $this->log('route_updated', $s, [
                'from' => $this->normalize($original['route_summary'] ?? null),
                'to'   => $this->normalize($changes['route_summary']),
            ]);
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
