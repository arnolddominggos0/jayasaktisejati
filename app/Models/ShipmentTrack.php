<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use App\Enums\TrackStatus;
use Illuminate\Database\Eloquent\Model;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;

class ShipmentTrack extends Model
{
    protected $table = 'shipment_tracks';

    protected $fillable = [
        'shipment_id',
        'status',
        'status_normalized',
        'tracked_at',
        'location',
        'note',
        'attachments',
        'check_result',
        'checkseet',
        'plan_loading_time_at',
        'plan_closing_time_at',
        'actual_loading_time_at',
        'actual_closing_time_at',
        'actual_berthing_time_at',
        'actual_unloading_start_time_at',
        'actual_unloading_end_time_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tracked_at' => 'datetime',
        'status'     => TrackStatus::class,
        'attachments' => 'array',
        'checkseet' => 'array',
        'check_result' => 'array',
        'plan_loading_time_at' => 'datetime',
        'plan_closing_time_at' => 'datetime',
        'actual_loading_time_at' => 'datetime',
        'actual_closing_time_at' => 'datetime',
        'actual_berthing_time_at' => 'datetime',
        'actual_unloading_start_time_at' => 'datetime',
        'actual_unloading_end_time_at' => 'datetime',
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
        $enum = $value instanceof TrackStatus
            ? $value
            : TrackStatus::normalize((string) $value);

        if (! $enum) {
            throw ValidationException::withMessages([
                'status' => 'Status tidak dikenal: ' . (string) $value,
            ]);
        }

        $this->attributes['status'] = $enum->value;
        $this->attributes['status_normalized'] = true;
    }

    /**
     * Run all tracking validations explicitly (used when model events are bypassed).
     */
    public function validateTrackingState(): void
    {
        $this->validateNoteForCriticalStatus();
        $this->validateChecksheetConsistency();
    }

    /**
     * Validate that note is provided for critical statuses on sea shipments.
     * Critical statuses: Hold, Cancelled
     */
    protected function validateNoteForCriticalStatus(): void
    {
        // Only validate when track is actually being recorded (not skeleton creation)
        if (empty($this->tracked_at)) {
            return;
        }

        $status = $this->status instanceof TrackStatus
            ? $this->status
            : TrackStatus::tryFrom((string) $this->status);

        if (! $status?->requiresNote()) {
            return;
        }

        // Only apply to sea shipments
        $shipment = $this->shipment;
        if (! $shipment || ($shipment->mode?->value ?? $shipment->mode) !== 'sea') {
            return;
        }

        $note = trim((string) $this->note);
        if (strlen($note) < 10) {
            throw ValidationException::withMessages([
                'note' => "Status \"{$status->label()}\" memerlukan catatan minimal 10 karakter.",
            ]);
        }
    }

    /**
     * Validate checksheet consistency for sea shipments.
     * If any checksheet item has status NG, note must be provided (min 10 chars).
     */
    protected function validateChecksheetConsistency(): void
    {
        // Only validate when track is actually being recorded (not skeleton creation)
        if (empty($this->tracked_at)) {
            return;
        }

        $shipment = $this->shipment;
        if (! $shipment || ($shipment->mode?->value ?? $shipment->mode) !== 'sea') {
            return;
        }

        $checkseet = $this->checkseet;
        if (! is_array($checkseet) || empty($checkseet)) {
            return;
        }

        $hasNg = false;
        foreach ($checkseet as $item) {
            if (is_array($item) && ($item['checkseet_status'] ?? null) === 'ng') {
                $hasNg = true;
                break;
            }
        }

        if (! $hasNg) {
            return;
        }

        $note = trim((string) $this->note);
        if (strlen($note) < 10) {
            throw ValidationException::withMessages([
                'note' => 'Checksheet memiliki status NG. Catatan minimal 10 karakter wajib diisi.',
            ]);
        }
    }

    protected static function booted(): void
    {
        static::creating(function (ShipmentTrack $track) {
            $uid = Filament::auth()?->id() ?: (auth()->check() ? auth()->id() : null);
            if ($uid) {
                $track->created_by ??= $uid;
                $track->updated_by ??= $uid;
            }

            // Validate note requirement for critical statuses on sea shipments
            $track->validateNoteForCriticalStatus();
            $track->validateChecksheetConsistency();
        });

        static::updating(function (ShipmentTrack $track) {
            // Validate note requirement when status changes to critical on sea shipments
            $track->validateNoteForCriticalStatus();
            $track->validateChecksheetConsistency();
        });

        static::saving(function (ShipmentTrack $track) {
            $status = $track->status instanceof \BackedEnum
                ? $track->status->value
                : (string) $track->status;

            $map = [
                'pickup'               => 10,
                'handover'             => 20,
                'stuffing'             => 30,
                'delivery_to_port'     => 40,
                'stacking'             => 50,
                'unit_loading'         => 60,
                'onship'               => 70,
                'vessel_depart'        => 80,
                'vessel_arrival'       => 90,
                'unloading'            => 100,
                'handover_trucking'    => 105,
                'delivery_to_customer' => 110,
                'delivered'            => 120,
                'hold'                 => 900,
                'cancelled'            => 999,
            ];

            if (!isset($map[$status])) {
                throw new \InvalidArgumentException("Unknown track status: {$status}");
            }

            $track->status_normalized = $map[$status];
        });

        static::updating(function (ShipmentTrack $track) {
            $uid = Filament::auth()?->id() ?: (auth()->check() ? auth()->id() : null);
            if ($uid) {
                $track->updated_by = $uid;
            }
        });

        static::saved(function (ShipmentTrack $track) {
            logger()->info('[SHIPMENT_TRACK_SAVED] fired', [
                'track_id'    => $track->id,
                'shipment_id' => $track->shipment_id,
                'status'      => $track->status instanceof \BackedEnum ? $track->status->value : (string) $track->status,
                'tracked_at'  => $track->tracked_at,
                'was_dirty'   => $track->wasChanged(),
            ]);

            $shipment = $track->shipment()->with('tracks')->first();
            if (!$shipment) return;

            $hasRealTracking = $shipment->tracks
                ->filter(fn($t) => !empty($t->tracked_at))
                ->isNotEmpty();

            $currentStatus = $shipment->status instanceof ShipmentStatus
                ? $shipment->status
                : ShipmentStatus::tryFrom(
                    $shipment->status instanceof \BackedEnum
                        ? $shipment->status->value
                        : (string) $shipment->status
                );

            logger()->info('[SHIPMENT_TRACK_SAVED] revert check', [
                'shipment_status'   => $currentStatus?->value,
                'has_real_tracking' => $hasRealTracking,
                'will_revert'       => !$hasRealTracking
                    && $currentStatus !== ShipmentStatus::Draft
                    && $currentStatus !== ShipmentStatus::Pending,
            ]);

            if (!$hasRealTracking) {
                // Pending + skeleton tracks (tracked_at = null) is a valid
                // post-sendToFc state. Do NOT revert — shipment is in the FC
                // queue awaiting its first real tracking event.
                if ($currentStatus === ShipmentStatus::Draft || $currentStatus === ShipmentStatus::Pending) {
                    return;
                }

                logger()->warning('[SHIPMENT_TRACK_SAVED] REVERTING SHIPMENT TO DRAFT', [
                    'shipment_id'    => $shipment->id,
                    'current_status' => $currentStatus?->value,
                ]);
                $shipment->status = ShipmentStatus::Draft->value;
                $shipment->saveQuietly();
                return;
            }

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
            if ($newStatus && $shipment->status !== $newStatus->value) {
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
