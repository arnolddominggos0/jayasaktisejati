<?php

namespace App\Observers;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
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


    public function created(Shipment $s): void
    {
        $status = $this->normalize($s->status);
        $this->log('created', $s, [
            'status'      => $status,
            'status_label' => $this->statusLabel($status),
        ]);
    }


    public function updated(Shipment $s): void
    {
        $fromStatus = $this->normalize($s->getOriginal('status'));
        $toStatus   = $this->normalize($s->status);

        $changes    = array_keys($s->getChanges());
        $changed    = fn(string $k) => in_array($k, $changes, true);

        $cancelChanged  = $changed('cancelled_at');
        $statusChanged  = $changed('status');

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
    }

    public function deleted(Shipment $s): void
    {
        $this->log('deleted', $s);
    }
    public function restored(Shipment $s): void
    {
        $this->log('restored', $s);
    }

    private function short(?string $v, int $max = 80): ?string
    {
        if ($v === null) return null;
        $v = trim($v);
        return mb_strlen($v) > $max ? (mb_substr($v, 0, $max - 1) . '…') : $v;
    }
}
