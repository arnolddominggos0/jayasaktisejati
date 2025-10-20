<?php

namespace App\Models;

use App\Enums\TrackStatus;
use App\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Model;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;

class ShipmentTrack extends Model
{
    protected $table = 'shipment_tracks';

    protected $fillable = [
        'shipment_id',
        'status',
        'tracked_at',
        'location',
        'note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tracked_at' => 'datetime',
        'status'     => TrackStatus::class,
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function setStatusAttribute($value): void
    {
        $enum = $value instanceof TrackStatus ? $value : TrackStatus::normalize((string) $value);
        if (!$enum) {
            throw ValidationException::withMessages(['status' => 'Status tidak dikenal: ' . (string) $value]);
        }
        $this->attributes['status'] = $enum->value;
        $this->attributes['status_normalized'] = true; 
    }

    protected static function booted(): void
    {
        static::creating(function (ShipmentTrack $track) {
            $uid = Filament::auth()?->id() ?: (auth()->check() ? auth()->id() : null);
            if ($uid) {
                $track->created_by ??= $uid;
                $track->updated_by ??= $uid;
            }
        });

        static::saving(function (ShipmentTrack $track) {
            // Normalisasi status bila diset sebagai string
            if (!($track->status instanceof TrackStatus)) {
                $track->status = TrackStatus::normalize((string) $track->status);
            }

            $shipment = $track->shipment()->with('tracks')->first();
            if (!$shipment) return;

            $statusVal = $track->status instanceof TrackStatus ? $track->status->value : (string) $track->status;
            $order = TrackStatus::orderForMode($shipment->mode);
            $orderVals = array_map(fn($e) => $e->value, $order);
            $idx = array_search($statusVal, $orderVals, true);

            if ($idx === false) {
                throw ValidationException::withMessages(['status' => 'Status tidak valid untuk moda ini.']);
            }

            // Validasi kronologi waktu
            if ($track->tracked_at) {
                for ($i = 0; $i < $idx; $i++) {
                    $prev = $shipment->tracks->firstWhere('status', $order[$i]->value);
                    if ($prev && $prev->tracked_at && $prev->tracked_at->gt($track->tracked_at)) {
                        throw ValidationException::withMessages(['tracked_at' => 'Waktu tidak boleh lebih awal dari langkah sebelumnya.']);
                    }
                }
                for ($j = $idx + 1; $j < count($order); $j++) {
                    $next = $shipment->tracks->firstWhere('status', $order[$j]->value);
                    if ($next && $next->tracked_at && $next->tracked_at->lt($track->tracked_at)) {
                        throw ValidationException::withMessages(['tracked_at' => 'Waktu tidak boleh lebih lambat dari langkah setelahnya yang sudah terisi.']);
                    }
                }
            }

            // Jika Cancelled/Delivered diisi, kosongkan langkah sesudahnya
            if (in_array($statusVal, [TrackStatus::Cancelled->value, TrackStatus::Delivered->value], true)) {
                foreach ($order as $k => $st) {
                    if ($k > $idx) {
                        $next = $shipment->tracks->firstWhere('status', $st->value);
                        if ($next && $next->tracked_at) {
                            $next->tracked_at = null;
                            $next->saveQuietly();
                        }
                    }
                }
            }
        });

        static::updating(function (ShipmentTrack $track) {
            $uid = Filament::auth()?->id() ?: (auth()->check() ? auth()->id() : null);
            if ($uid) {
                $track->updated_by = $uid;
            }
        });

        static::saved(function (ShipmentTrack $track) {
            $shipment = $track->shipment()->with('tracks')->first();
            if (!$shipment) return;

            $order = TrackStatus::orderForMode($shipment->mode);
            $indexMap = [];
            foreach ($order as $i => $e) {
                $indexMap[$e->value] = $i;
            }

            $reached = $shipment->tracks
                ->filter(fn($t) => !empty($t->tracked_at))
                ->sortBy(fn($t) => $indexMap[$t->status instanceof TrackStatus ? $t->status->value : (string)$t->status] ?? 999)
                ->last();

            $newStatus = $reached?->status?->toShipmentStatus();
            if ($newStatus && (string)$shipment->status !== $newStatus->value) {
                $shipment->status = $newStatus->value;

                if ($newStatus === ShipmentStatus::Delivered) {
                    if (Shipment::hasCol('delivered_at')) {
                        $shipment->delivered_at = $reached?->tracked_at ?: now();
                    }
                    $shipment->cancelled_at = null;
                    $shipment->cancelled_by = null;
                }

                if ($newStatus === ShipmentStatus::Cancelled) {
                    if (Shipment::hasCol('cancelled_at')) {
                        $shipment->cancelled_at = $shipment->cancelled_at ?: now();
                    }
                    $shipment->cancelled_by = $shipment->cancelled_by ?: (auth()->id() ?: null);
                    if (Shipment::hasCol('delivered_at')) {
                        $shipment->delivered_at = null;
                    }
                }

                $shipment->saveQuietly();
            }
        });
    }
}
